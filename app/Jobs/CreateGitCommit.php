<?php

namespace App\Jobs;

use App\Formula;
use Illuminate\Queue\SerializesModels;
use SebastianBergmann\Git\Git;
use TQ\Git\Repository\Repository;

class CreateGitCommit
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
     * Formula name.
     *
     * @var string
     */
    protected $name;

    /**
     * Create a new job instance.
     *
     * @param Formula $formula
     */
    public function __construct(Formula $formula)
    {
        $this->formula = $formula;

        $this->git = new Git($this->formula->getAttribute('git_repo'));

        $this->repository = Repository::open($this->formula->getAttribute('git_repo'), $this->binary());
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->extractName()
            ->master()
            ->prBranch()
            ->modify()
            ->commit()
            ->master();
    }

    /**
     * Extract the formula name.
     *
     * @return $this
     */
    protected function extractName()
    {
        $name = $this->formula->getAttribute('name');

        $pos = strrpos($name, '/');

        $this->name = false === $pos ? $name : substr($name, $pos + 1);

        return $this;
    }

    /**
     * Create pull request branch.
     *
     * @return $this
     */
    protected function prBranch()
    {
        $branch = sprintf('-b %s-%s', $this->name, $this->formula->getAttribute('version'));

        $this->git->checkout($branch);

        return $this;
    }

    /**
     * Update formula url and hash.
     *
     * @return $this
     */
    protected function modify()
    {
        $filename = sprintf('%s/%s.rb', $this->formula->getAttribute('git_repo'), $this->name);

        $regex = $this->regex();

        $content = preg_replace(
            $regex['patterns'],
            $regex['replacements'],
            file_get_contents($filename)
        );

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
        $hash = explode(':', $this->formula->getAttribute('hash'));

        $patterns = [
            '/url ".+"'.PHP_EOL.'/U',
            '/sha\d{3} ".+"'.PHP_EOL.'/U',
        ];

        $replacements = [
            sprintf('url "%s"%s', $this->formula->getAttribute('archive'), PHP_EOL),
            sprintf('%s "%s"%s', $hash[0], $hash[1], PHP_EOL),
        ];

        return compact('patterns', 'replacements');
    }

    /**
     * Commits the currently staged changes into the repository.
     *
     * @return $this
     */
    protected function commit()
    {
        $message = sprintf('%s %s', $this->name, $this->formula->getAttribute('version'));

        $this->repository->add();

        $this->repository->commit($message);

        return $this;
    }

    /**
     * Change the branch to master.
     *
     * @return $this
     */
    protected function master()
    {
        $this->git->checkout('master');

        return $this;
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
