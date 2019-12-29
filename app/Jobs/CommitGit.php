<?php

namespace App\Jobs;

use App\Exceptions\NothingToCommitException;
use App\Models\Formula;
use Github\Client as GithubClient;
use Github\Exception\MissingArgumentException;
use GuzzleHttp\Client;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CommitGit
{
    use SerializesModels;

    /**
     * Formula model instance.
     *
     * @var Formula
     */
    protected $formula;

    /**
     * Git repo path.
     *
     * @var string
     */
    protected $cwd;

    /**
     * Github api client.
     *
     * @var GithubClient
     */
    protected $github;

    /**
     * Debug info.
     *
     * @var array
     */
    protected $debug = [
        'file' => [
            'size' => 0,
            'fetch' => 0,
        ],
    ];

    /**
     * Create a new job instance.
     *
     * @param Formula $formula
     */
    public function __construct(Formula $formula)
    {
        $this->formula = $formula;

        $this->cwd = $this->formula->git['path'];

        $this->github = app('github');
    }

    /**
     * Execute the job.
     *
     * @return void
     *
     * @throws MissingArgumentException
     */
    public function handle(): void
    {
        try {
            // checkout homebrew repo to master branch
            $this->master()
                // create new branch for the release
                ->createBranch()
                // update homebrew repo formula
                ->modifyFormula()
                // update related formulas revision
                ->modifyRevision()
                // update dependent formulas url and hash
                ->modifyDependent()
                // commit homebrew repo
                ->commit()
                // push commit new remote tracked repository
                ->pushCommit()
                // close last pull request if it still open
                ->closeLastOpenPullRequest()
                // create pull request to homebrew project
                ->openPullRequest()
                // checkout homebrew repo to master branch
                ->master();
        } catch (NothingToCommitException $e) {
            Log::error('nothing-to-commit', [
                'formula' => $this->formula->name,
                'version' => $this->formula->version,
            ]);

            // revert changes
            $this->revert();
        }
    }

    /**
     * Change branch to master.
     *
     * @return $this
     */
    protected function master(): self
    {
        $this->cmd('git checkout master');

        return $this;
    }

    /**
     * Create new branch for the update.
     *
     * @return $this
     */
    protected function createBranch(): self
    {
        $this->cmd(sprintf('git checkout -b %s', $this->branchName()));

        return $this;
    }

    /**
     * Modify formula url and hash.
     *
     * @param string|null $formula
     *
     * @return $this
     */
    protected function modifyFormula($formula = null): self
    {
        // get formula path
        $filename = sprintf('%s/%s.rb',
            $this->cwd,
            $formula ?: mb_strtolower($this->name())
        );

        // get regex pattern
        $regex = $this->regex();

        // update formula's url and hash
        $content = preg_replace(
            $regex['patterns'],
            $regex['replacements'],
            file_get_contents($filename),
            1,
            $count
        );

        // if $count is zero, nothing change
        if (0 === $count) {
            if (!is_null($formula)) {
                Log::error('dependent-nothing-to-commit', compact('formula'));

                return $this;
            }

            throw new NothingToCommitException;
        }

        // write data to file
        file_put_contents($filename, $content, LOCK_EX);

        return $this;
    }

    /**
     * Get preg_replace regex.
     *
     * @return array
     */
    protected function regex(): array
    {
        $hash = $this->hashRemoteFile($this->formula->archive_url);

        $patterns = [
            '/url ".+"'.PHP_EOL.'/U',
            '/sha\d{3} ".+"'.PHP_EOL.'/U',
        ];

        $replacements = [
            sprintf('url "%s"%s', $this->formula->archive_url, PHP_EOL),
            sprintf('%s "%s"%s', 'sha256', $hash, PHP_EOL),
        ];

        return compact('patterns', 'replacements');
    }

    /**
     * Get remote file hash.
     *
     * @param string $url
     *
     * @return string|null
     */
    protected function hashRemoteFile(string $url): ?string
    {
        $time = microtime(true);

        $response = (new Client)->get($url, ['http_errors' => false]);

        $this->debug['file']['fetch'] = number_format(microtime(true) - $time, 1);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $this->debug['file']['size'] = number_format($response->getBody()->getSize());

        return hash('sha256', $response->getBody()->getContents());
    }

    /**
     * Modify revision formulas revision.
     *
     * @return $this
     */
    protected function modifyRevision(): self
    {
        $formulas = $this->formula->revision;

        if (is_null($formulas) || empty($formulas)) {
            return $this;
        }

        foreach ($formulas as $formula) {
            $filename = sprintf('%s/%s.rb', $this->cwd, $formula);

            $content = file_get_contents($filename);

            if (false === ($pos = mb_strpos($content, 'revision'))) {
                $revision = ' revision 1'.PHP_EOL;

                // (sha256 "xxx").length + new line
                $begin = $end = mb_strpos($content, 'sha256') + 74;
            } else {
                $pos += 9;

                $endPos = mb_strpos($content, PHP_EOL, $pos);

                $revision = intval(mb_substr($content, $pos, $endPos - $pos)) + 1;

                $begin = $pos;
                $end = $endPos;
            }

            $content = sprintf(
                '%s%s%s',
                mb_substr($content, 0, $begin),
                $revision,
                mb_substr($content, $end)
            );

            file_put_contents($filename, $content, LOCK_EX);
        }

        return $this;
    }

    /**
     * Modify dependent formulas url and hash.
     *
     * @return $this
     */
    protected function modifyDependent(): self
    {
        $formulas = $this->formula->dependent;

        if (is_null($formulas) || empty($formulas)) {
            return $this;
        }

        foreach ($formulas as $formula) {
            $this->modifyFormula($formula);
        }

        return $this;
    }

    /**
     * Commits the currently staged changes into the repository.
     *
     * @return $this
     */
    protected function commit(): self
    {
        $this->cmd('git add --all');

        $temp = tempnam(sys_get_temp_dir(), 'homebrew-updater-');

        file_put_contents($temp, sprintf('%s %s', $this->name(), $this->formula->version));

        $this->cmd(sprintf('git commit --author="homebrew-updater<bepsvpt/homebrew-updater>" --file %s', $temp));

        unlink($temp);

        return $this;
    }

    /**
     * Push commit to GitHub.
     *
     * @return $this
     */
    protected function pushCommit(): self
    {
        $this->cmd(sprintf('git push origin %s', $this->branchName()));

        return $this;
    }

    /**
     * Close last pull request if it still open.
     *
     * @return $this
     */
    protected function closeLastOpenPullRequest(): self
    {
        if (is_null($prUrl = $this->formula->pull_request)) {
            return $this;
        }

        $arguments = $this->formula->git['upstream'];

        array_push($arguments, Arr::last(explode('/', $prUrl)));

        $pullRequest = $this->github->pullRequests()->show(...$arguments);

        if ('open' === $pullRequest['state']) {
            array_push($arguments, ['state' => 'closed']);

            $this->github->pullRequests()->update(...$arguments);
        }

        return $this;
    }

    /**
     * Open a pull request for homebrew repository.
     *
     * @return $this
     *
     * @throws MissingArgumentException
     */
    protected function openPullRequest(): self
    {
        $github = $this->formula->git;

        $pullRequest = $this->github
            ->pullRequests()
            ->create($github['upstream']['owner'], $github['upstream']['repo'], [
                'title' => sprintf('%s %s', $this->name(), $this->formula->version),
                'head' => sprintf('%s:%s', $github['fork']['owner'], $this->branchName()),
                'base' => 'master',
                'body' => $this->pullRequestBody(),
            ]);

        DB::table('formulas')
            ->where($this->formula->getKeyName(), '=', $this->formula->getKey())
            ->update(['pull_request' => $pullRequest['html_url']]);

        return $this;
    }

    /**
     * Get pull request body.
     *
     * @return string
     */
    protected function pullRequestBody(): string
    {
        $version = json_decode(file_get_contents(base_path('composer.json')))->version;

        return <<<EOF
---

Debug Info:
- homebrew updater version: {$version}
- formula new file size: {$this->debug['file']['size']} bytes
- formula fetch time: {$this->debug['file']['fetch']} seconds

Pull request opened by [homebrew-updater](https://github.com/bepsvpt/homebrew-updater) project.
EOF;
    }

    /**
     * Revert to original state.
     *
     * @return $this
     */
    protected function revert(): self
    {
        // checkout to master branch
        $this->master();

        // delete the branch that we created
        $this->cmd(sprintf('git branch -D %s', $this->branchName()));

        return $this;
    }

    /**
     * Get the pull request branch name.
     *
     * @return string
     */
    protected function branchName(): string
    {
        // branch name is combine with {repo-name}-{new-version}
        return sprintf('%s-%s', $this->name(), $this->formula->version);
    }

    /**
     * Get the formula name.
     *
     * @return string
     */
    protected function name(): string
    {
        // if formula's name is homebrew/xxx/zzz, we only need `zzz`
        return Arr::last(explode('/', $this->formula->name));
    }

    /**
     * Execute command.
     *
     * @param string $cmd
     *
     * @return Process
     *
     * @throws ProcessFailedException
     */
    protected function cmd(string $cmd): Process
    {
        return (new Process(explode(' ', $cmd), $this->cwd))->mustRun();
    }
}
