<?php

namespace App\Console\Commands\Upstreams;

use App\Models\Formula;
use Illuminate\Console\Command;
use TQ\Git\Repository\Repository;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'upstream:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync local repositories to remote tracked repositories';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        foreach ($this->upstreams() as $repo) {
            // retrieve repo path in filesystem
            $repository = Repository::open($repo['path'], $this->binary());

            // checkout git repo to master branch
            $repository->getGit()->{'checkout'}($repository->getRepositoryPath(), ['master']);

            // ensure the repo has remote tracked repository which name is upstream
            if (! isset($repository->getCurrentRemote()['upstream'])) {
                $this->setUpUpstream($repo['upstream'], $repository);
            }

            // fetch upstream master branch to local
            $repository->getGit()->{'fetch'}($repository->getRepositoryPath(), ['upstream', 'master']);

            // rebase upstream/master to local master branch
            $repository->getGit()->{'rebase'}($repository->getRepositoryPath(), ['upstream/master']);

            // push local master branch to GitHub
            $repository->getGit()->{'push'}($repository->getRepositoryPath(), ['origin', 'master']);
        }
    }

    /**
     * Get all formulas unique git upstreams.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function upstreams()
    {
        return Formula::all('git')
            ->pluck('git')
            ->unique();
    }

    /**
     * Set up upstream for the repository.
     *
     * @param array $upstream
     * @param Repository $repository
     */
    protected function setUpUpstream($upstream, Repository $repository)
    {
        $upstream = sprintf('https://github.com/%s/%s', $upstream['owner'], $upstream['repo']);

        $arguments = ['add', 'upstream', $upstream];

        $repository->getGit()->{'remote'}($repository->getRepositoryPath(), $arguments);
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
