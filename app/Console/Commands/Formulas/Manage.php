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
        // specific the fields that we want to retrieve
        $headers = ['id', 'name', 'url', 'checker', 'version', 'checked_at'];

        // get all formulas with specific fields
        $formulas = $this->formula->all($headers);

        // if full option is not set, we should shorten some fields
        if (! $this->option('full')) {
            // iterator all formulas and shorten them
            $formulas->each(function ($formula) {
                $this->shorten($formula);
            });
        }

        // if sort option is set, sort by the specific field
        if ($this->option('sort')) {
            $formulas = $formulas->sortBy($this->option('sort'));
        }

        // display the result as a table
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

        // if formula's name is homebrew/xxx/zzz, we only need `zzz`
        if (false !== ($pos = strrpos($f['name'], '/'))) {
            $formula->setAttribute('name', substr($f['name'], $pos + 1));
        }

        // if formula's checker is inherited from GitHub, remove the giithub prefix url
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
        // get the formula's full name, e.g. homebrew/php/phpmyadmin
        $name = $this->ask('Full formula name');

        // get the formula's repo url, only support GitHub now
        // e.g. https://github.com/phpmyadmin/phpmyadmin
        $url = $this->ask('Repository url');

        // get the formula's checker, e.g. Github
        $checker = $this->ask('Checker');

        // ensure the checker is exist
        if (! class_exists($this->checker($checker))) {
            return $this->error('Checker dost not exist.');
        }

        // get the local git repo path
        $git = $this->askGit();

        // save the formula to database
        $this->formula->create(compact('name', 'url', 'checker', 'git'));
    }

    /**
     * Ask git information.
     *
     * @return array
     */
    protected function askGit()
    {
        do {
            // get the homebrew repo path
            if (false === ($path = $this->askRepoPath())) {
                $this->error('Repository dost not exist.');
            }
        } while (false === $path);

        // get the upstream repo owner and name, e.g. `homebrew/php`
        $upstream = [
            'owner' => $this->ask('Upstream owner', $this->gitCommonField('upstream.owner')) ?: null,
            'repo' => $this->ask('Upstream repo', $this->gitCommonField('upstream.repo')) ?: null,
        ];

        // get the fork repo owner
        $fork = [
            'owner' => $this->ask('Fork owner', $this->gitCommonField('fork.owner')) ?: null,
        ];

        // return all information as an associative array
        return compact('path', 'upstream', 'fork');
    }

    /**
     * Ask for repository path.
     *
     * @return null|false|string
     */
    protected function askRepoPath()
    {
        // get the homebrew repo path
        $repo = $this->ask('Local homebrew repository path', $this->gitCommonField('path'));

        if (false === $repo) {
            return null;
        }

        // if the path starts with `~/`, we assume it starts from home directory
        // if the path starts with `.`, we assume it starts from this project base directory
        if (starts_with($repo, '~/')) {
            $repo = $_SERVER['HOME'].'/'.substr($repo, 2);
        } elseif (starts_with($repo, '.')) {
            $repo = base_path($repo);
        }

        // return canonicalized absolute pathname
        return realpath($repo);
    }

    /**
     * Get most common field.
     *
     * @param string $field
     *
     * @return bool|string
     */
    protected function gitCommonField($field)
    {
        // because this method will call multiple times, use this method to optimize it
        static $git = null;

        if (is_null($git)) {
            // get all formulas' git field
            $git = $this->formula->all(['git']);
        }

        // count the number of occurrences of specific field
        $count = array_count_values($git->pluck("git.{$field}")->toArray());

        // if there is no data, just return false
        if (empty($count)) {
            return false;
        }

        // return the most frequent value
        return array_search(max($count), $count);
    }

    /**
     * Delete a repository from monitored list.
     *
     * @return void
     */
    protected function destroy()
    {
        // get the target formula id
        $id = $this->ask('Target id');

        // confirm to delete the formula
        if ($this->confirm("Delete formula id '{$id}'")) {
            // delete it
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
        // get the path to store the backup file
        $path = $this->ask('Path to save backup file');

        // if the path is a directory, append the filename
        if (is_dir($path)) {
            $path .= '/homebrew-updater.json';
        }

        // transform the formulas to json format and save them
        file_put_contents($path, $this->formula->all()->toJson(), LOCK_EX);

        // show success message
        $this->info('Backup succeed!');
    }

    /**
     * Restore formulas from file.
     *
     * @return void
     */
    protected function restore()
    {
        // get the backup file path
        $path = $this->ask('Backup file');

        // if the path is not a file, show error message
        if (! is_file($path)) {
            return $this->error('Backup file not exists.');
        }

        // get the file content and decode it
        $formulas = json_decode(file_get_contents($path), true);

        // save the formulas to database
        DB::table($this->formula->getTable())->insert($formulas);

        // show success message
        $this->info('Restore succeed!');
    }
}
