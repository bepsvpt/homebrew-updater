<?php

namespace App\Console\Commands\Formulas;

use Carbon\Carbon;
use Composer\Semver\Comparator;

class Check extends Formula
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formula:check {--f|formula=* : Specify formulas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check formulas have new release.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->formulas()
            ->each(function (\App\Formula $formula) {
                $this->info(sprintf('Checking %s ...', $formula->getAttribute('name')));

                $formula->update(array_merge(
                    $this->watchdog($formula),
                    ['checked_at' => Carbon::now()]
                ));
            });
    }

    /**
     * Get formulas model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function formulas()
    {
        $formulas = $this->option('formula');

        if (empty($formulas)) {
            return $this->formula->get();
        }

        return $this->formula->whereIn('name', $formulas)->get();
    }

    /**
     * Get formula latest release info.
     *
     * @param \App\Formula $formula
     *
     * @return array
     */
    protected function watchdog(\App\Formula $formula)
    {
        $checker = $this->checker($formula);

        $latest = $checker->latest();

        if (Comparator::greaterThan($checker->version($latest), $checker->version($formula->getAttribute('version')))) {
            $version = $latest;

            list('url' => $archive, 'hash' => $hash) = $checker->archive();
        }

        return compact('version', 'archive', 'hash');
    }
}
