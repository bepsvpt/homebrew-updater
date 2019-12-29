<?php

namespace App\Console\Commands\Upstreams;

use App\Models\Formula;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

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
     * Git command execute cwd.
     *
     * @var string|null
     */
    protected $cwd = null;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        foreach ($this->upstreams() as $repo) {
            $this->cwd = $repo['path'];

            // checkout git repo to master branch
            $this->cmd('git checkout master');

            try {
                // ensure repo has remote upstream
                $this->cmd('git remote get-url upstream');
            } catch (ProcessFailedException $e) {
                // setup remote upstream
                $this->cmd(sprintf(
                    'git remote add upstream https://github.com/%s/%s',
                    $repo['upstream']['owner'],
                    $repo['upstream']['repo']
                ));
            }

            // fetch upstream master branch to local
            $this->cmd('git fetch upstream master');

            // rebase upstream/master to local master branch
            $this->cmd('git rebase upstream/master');

            // push local master branch to GitHub
            $this->cmd('git push origin master');
        }
    }

    /**
     * Get all formulas unique git upstreams.
     *
     * @return Collection
     */
    protected function upstreams(): Collection
    {
        return Formula::all('git')
            ->pluck('git')
            ->unique();
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
