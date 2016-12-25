<?php

namespace App\Console\Commands\Formulas;

use DB;

class Manage extends Formula
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formula:manage {--add} {--delete} {--backup} {--restore} {--full} {--s|sort= : Sort by column}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage the formulas.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('add')) {
            $this->store();
        } elseif ($this->option('delete')) {
            $this->destroy();
        } elseif ($this->option('backup')) {
            $this->backup();
        } elseif ($this->option('restore')) {
            $this->restore();
        }

        $this->index();
    }

    /**
     * Show the repositories monitored list.
     *
     * @return void
     */
    protected function index()
    {
        $headers = ['id', 'name', 'url', 'checker', 'version', 'checked_at'];

        $formulas = $this->formula->all($headers);

        if (! $this->option('full')) {
            $formulas->each(function ($formula) {
                $this->shorten($formula);
            });
        }

        if ($this->option('sort')) {
            $formulas = $formulas->sortBy($this->option('sort'));
        }

        $this->table($headers, $formulas);
    }

    /**
     * Shorten formulas information.
     *
     * @param \App\Models\Formula $formula
     *
     * @return void
     */
    protected function shorten(\App\Models\Formula $formula)
    {
        $f = $formula->getAttributes();

        $formula->setAttribute('name', substr($f['name'], strrpos($f['name'], '/') + 1));

        if (is_a($this->checker($f['checker']), $this->checker('Github'), true)) {
            $formula->setAttribute('url', str_replace('https://github.com/', '', $f['url']));
        }
    }

    /**
     * Add a new repository to monitored list.
     *
     * @return void
     */
    protected function store()
    {
        $name = $this->ask('Full formula name');

        $url = $this->ask('Repository url');

        $checker = $this->ask('Checker');

        if (! class_exists($this->checker($checker))) {
            return $this->error('Checker dost not exist.');
        }

        $git_repo = $this->askRepoPath();

        if (false === $git_repo) {
            return $this->error('Repository dost not exist.');
        }

        $this->formula->create(compact('name', 'url', 'checker', 'git_repo'));
    }

    /**
     * Ask for repository path.
     *
     * @return null|false|string
     */
    protected function askRepoPath()
    {
        $repo = $this->ask('Local homebrew repository path', 'null');

        if ('null' === $repo) {
            return null;
        } elseif (starts_with($repo, '~/')) {
            $repo = $_SERVER['HOME'].'/'.substr($repo, 2);
        } elseif (starts_with($repo, '.')) {
            $repo = base_path($repo);
        }

        return realpath($repo);
    }

    /**
     * Delete a repository from monitored list.
     *
     * @return void
     */
    protected function destroy()
    {
        $id = $this->ask('Target id');

        if ($this->confirm("Delete formula id '{$id}'")) {
            $this->formula->destroy($id);
        }
    }

    /**
     * Backup formulas.
     *
     * @return void
     */
    protected function backup()
    {
        $path = $this->ask('Path to save backup file');

        if (is_dir($path)) {
            $path .= '/homebrew-updater.json';
        }

        file_put_contents($path, $this->formula->all()->toJson(), LOCK_EX);

        $this->info('Backup succeed!');
    }

    /**
     * Restore formulas from file.
     *
     * @return void
     */
    protected function restore()
    {
        $path = $this->ask('Backup file');

        if (! is_file($path)) {
            return $this->error('Backup file not exists.');
        }

        $formulas = json_decode(file_get_contents($path), true);

        DB::table($this->formula->getTable())->insert($formulas);

        $this->info('Restore succeed!');
    }
}
