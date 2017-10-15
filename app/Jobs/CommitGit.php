<?php

namespace App\Jobs;

use App\Exceptions\NothingToCommitException;
use App\Models\Formula;
use DB;
use GitHub;
use Illuminate\Queue\SerializesModels;
use Log;
use SebastianBergmann\Git\Git;
use TQ\Git\Repository\Repository;

class CommitGit
{
    use SerializesModels;

    /**
     * @var Formula
     */
    protected $formula;

    /**
     * @var Git
     */
    protected $git;

    /**
     * @var Repository
     */
    protected $repository;

    /**
     * Hash Algorithm.
     *
     * @var string
     */
    protected $hash = 'sha256';

    /**
     * Create a new job instance.
     *
     * @param Formula $formula
     */
    public function __construct(Formula $formula)
    {
        $this->formula = $formula;

        $this->git = new Git($this->formula->getAttribute('git')['path']);

        $this->repository = Repository::open($this->formula->getAttribute('git')['path'], $this->binary());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
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
                ->closeLastPullRequest()
                // create pull request to homebrew project
                ->openPullRequest()
                // checkout homebrew repo to master branch
                ->master();
        } catch (NothingToCommitException $e) {
            Log::error('nothing-to-commit', [
                'formula' => $this->formula->getAttribute('name'),
                'version' => $this->formula->getAttribute('version'),
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
    protected function master()
    {
        $this->git->checkout('master');

        return $this;
    }

    /**
     * Create new branch for the update.
     *
     * @return $this
     */
    protected function createBranch()
    {
        $branch = sprintf('-b %s', $this->branchName());

        $this->git->checkout($branch);

        return $this;
    }

    /**
     * Modify formula url and hash.
     *
     * @param string|null $formula
     *
     * @return $this
     */
    protected function modifyFormula($formula = null)
    {
        // get formula path
        $filename = sprintf('%s/%s.rb', $this->formula->getAttribute('git')['path'], $formula ?: mb_strtolower($this->name()));

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
            if (! is_null($formula)) {
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
    protected function regex()
    {
        static $hash = null;

        $url = $this->formula->getAttribute('archive_url');

        if (is_null($hash)) {
            $hash = hash_remote($this->hash, $url);
        }

        $patterns = [
            '/url ".+"'.PHP_EOL.'/U',
            '/sha\d{3} ".+"'.PHP_EOL.'/U',
        ];

        $replacements = [
            sprintf('url "%s"%s', $url, PHP_EOL),
            sprintf('%s "%s"%s', 'sha256', $hash, PHP_EOL),
        ];

        return compact('patterns', 'replacements');
    }

    /**
     * Modify revision formulas revision.
     *
     * @return $this
     */
    protected function modifyRevision()
    {
        $formulas = $this->formula->getAttribute('revision');

        if (is_null($formulas) || empty($formulas)) {
            return $this;
        }

        $path = $this->formula->getAttribute('git')['path'];

        foreach ($formulas as $formula) {
            $filename = sprintf('%s/%s.rb', $path, $formula);

            $content = file_get_contents($filename);

            if (false === ($pos = mb_strpos($content, 'revision'))) {
                $revision = '  revision 1'.PHP_EOL;

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
    protected function modifyDependent()
    {
        $formulas = $this->formula->getAttribute('dependent');

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
    protected function commit()
    {
        $message = sprintf('%s %s', $this->name(), $this->formula->getAttribute('version'));

        $this->repository->add();

        $this->repository->commit($message);

        return $this;
    }

    /**
     * Push commit to GitHub.
     *
     * @return $this
     */
    protected function pushCommit()
    {
        $arguments = ['origin', $this->branchName()];

        $this->repository->getGit()->{'push'}($this->repository->getRepositoryPath(), $arguments);

        return $this;
    }

    /**
     * Close last pull request if it still open.
     *
     * @return $this
     */
    protected function closeLastPullRequest()
    {
        if (is_null($prUrl = $this->formula->getAttribute('pull_request'))) {
            return $this;
        }

        $upstream = $this->formula->getAttribute('git')['upstream'];

        $prId = array_last(explode('/', $prUrl));

        $pullRequest = GitHub::pullRequests()->show($upstream['owner'], $upstream['repo'], $prId);

        if ('open' === $pullRequest['state']) {
            GitHub::pullRequests()->update(
                $upstream['owner'],
                $upstream['repo'],
                $prId,
                ['state' => 'closed']
            );
        }

        return $this;
    }

    /**
     * Open a pull request for homebrew repository.
     *
     * @return $this
     */
    protected function openPullRequest()
    {
        $github = $this->formula->getAttribute('git');

        $pullRequest = GitHub::pullRequests()
            ->create($github['upstream']['owner'], $github['upstream']['repo'], [
                'title' => sprintf('%s %s', $this->name(), $this->formula->getAttribute('version')),
                'head'  => sprintf('%s:%s', $github['fork']['owner'], $this->branchName()),
                'base'  => 'master',
                'body'  => $this->pullRequestBody(),
            ]);

        DB::table('formulas')
            ->where($this->formula->getKeyName(), $this->formula->getKey())
            ->update(['pull_request' => $pullRequest['html_url']]);

        return $this;
    }

    /**
     * Get pull request body.
     *
     * @return string
     */
    protected function pullRequestBody()
    {
        return <<<'EOF'
---

Pull request opened by [homebrew-updater](https://github.com/BePsvPT/homebrew-updater) project.
EOF;
    }

    /**
     * Revert to original state.
     *
     * @return $this
     */
    protected function revert()
    {
        // checkout to master branch
        $this->master();

        // delete the branch that we created
        $branch = sprintf('%s', $this->branchName());

        $arguments = ['-D', $branch];

        $this->repository->getGit()->{'branch'}($this->repository->getRepositoryPath(), $arguments);

        return $this;
    }

    /**
     * Get the pull request branch name.
     *
     * @return string
     */
    protected function branchName()
    {
        // branch name is combine with {repo-name}-{new-version}
        return sprintf('%s-%s', $this->name(), $this->formula->getAttribute('version'));
    }

    /**
     * Get the formula name.
     *
     * @return $this
     */
    protected function name()
    {
        // if formula's name is homebrew/xxx/zzz, we only need `zzz`
        return array_last(explode('/', $this->formula->getAttribute('name')));
    }

    /**
     * Git binary file.
     *
     * @return string
     */
    protected function binary()
    {
        if (is_file('/usr/local/bin/git')) {
            return '/usr/local/bin/git';
        }

        return '/usr/bin/git';
    }
}
