<?php

namespace App\Console\Commands\Formulas;

class Manage extends Formula
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formula:manage {--add} {--delete} {--backup} {--restore}';

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

        $this->table($headers, $formulas);
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

        if (! class_exists("App\\Checkers\\$checker")) {
            return $this->error('Checker not exists.');
        }

        $this->formula->create(compact('name', 'url', 'checker'));
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

    protected function backup()
    {
        $path = $this->ask('Path to save backup file');

        if (is_dir($path)) {
            $path .= '/homebrew-formula-updater.json';
        }

        file_put_contents($path, $this->formula->all()->toJson(), LOCK_EX);

        $this->info('Backup succeed!');
    }

    protected function restore()
    {
        $path = $this->ask('Backup file');

        if (! is_file($path)) {
            return $this->error('Backup file not exists.');
        }

        $formulas = json_decode(file_get_contents($path), true);

        foreach ($formulas as $formula) {
            $this->formula->insert($formula);
        }

        $this->info('Restore succeed!');
    }
}
