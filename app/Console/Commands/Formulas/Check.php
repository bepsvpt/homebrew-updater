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
    protected $description = 'Check formulas\' new release.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // iterators all formulas
        $this->formulas()
            ->each(function (\App\Models\Formula $formula) {
                $this->info(sprintf('Checking %s ...', $formula->getAttribute('name')));

                // update the formula's information to latest status
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

        // if formula option is not set, return all formulas
        if (empty($formulas)) {
            return $this->formula->where('enable', true)->get();
        }

        // get specific formulas
        return $this->formula->whereIn('name', $formulas)->get();
    }

    /**
     * Get formula latest release info.
     *
     * @param \App\Models\Formula $formula
     *
     * @return array
     */
    protected function watchdog(\App\Models\Formula $formula)
    {
        // get the formula's checker
        $checker = $this->checker($formula);

        // check the formula has new release or not
        $latest = $checker->latest();

        // if there is new release, set up variables
        if (Comparator::greaterThan($checker->version($latest), $checker->version($formula->getAttribute('version')))) {
            // update version
            $version = $latest;
        }

        // return all information as an associative array
        return compact('version');
    }
}
