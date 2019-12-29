<?php

namespace App\Console\Commands\Formulas;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        $headers = ['id', 'name', 'repo', 'version', 'enable', 'checked_at'];

        // get all formulas with specific fields
        $formulas = $this->formula->all($headers);

        // if full option is not set, we should shorten some fields
        if (! $this->option('full')) {
            // iterator all formulas for shortening them
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
        $formula->setAttribute('name', Arr::last(explode('/', $f['name'])));
    }

    /**
     * Add a new repository to monitored list.
     *
     * @return void
     */
    protected function store()
    {
        // get formula's full name, e.g. homebrew/php/phpmyadmin
        $name = $this->ask('Full formula name');

        // get formula's repo, only support GitHub now
        // e.g. https://github.com/phpmyadmin/phpmyadmin
        $repo = $this->ask('Repository name');

        // get formula's checker, e.g. Github
        $checker = $this->choice('Checker', ['Github'], 0);

        // get formula's archive template
        $archive = $this->ask('Archive template');

        // get local git repo path
        $git = $this->askGit();

        // get revision formulas
        $revision = $this->ask('Revision formulas, separate by comma, e.g. phpmyadmin,wp-cli', false) ?: null;

        if (! is_null($revision)) {
            $revision = array_map(function ($name) {
                return mb_strtolower(trim($name));
            }, explode(',', $revision));
        }

        // get dependent formulas
        $dependent = $this->ask('Dependent formulas, separate by comma, e.g. phpmyadmin,wp-cli', false) ?: null;

        if (! is_null($dependent)) {
            $dependent = array_map(function ($name) {
                return mb_strtolower(trim($name));
            }, explode(',', $dependent));
        }

        // save formula to database
        $this->formula->create(compact('name', 'repo', 'checker', 'archive', 'git', 'revision', 'dependent'));
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
        if (Str::startsWith($repo, '~/')) {
            $repo = $_SERVER['HOME'].'/'.substr($repo, 2);
        } elseif (Str::startsWith($repo, '.')) {
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
