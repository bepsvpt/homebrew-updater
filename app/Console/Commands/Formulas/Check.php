<?php

namespace App\Console\Commands\Formulas;

use App\Models\Formula as FormulaModel;
use Composer\Semver\Comparator;
use Illuminate\Database\Eloquent\Collection;

class Check extends Formula
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'formula:check {--f|formula= : Specify formula}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check formulas\' new release.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->formulas()->each(function (FormulaModel $formula) {
            $this->info(sprintf('Checking formula %s...', $formula->name));

            // update the formula's information to latest status
            $formula->update(array_merge(
                $this->watchdog($formula),
                ['checked_at' => now()]
            ));
        });
    }

    /**
     * Get formulas model.
     *
     * @return Collection
     */
    protected function formulas(): Collection
    {
        $name = $this->option('formula');

        $query = $this->formula->newQuery();

        // if formula option is not set, return all formulas
        if (empty($name)) {
            return $query->where('enable', '=', true)->get();
        }

        // get specific formulas
        return $query->where('name', '=', $name)->get();
    }

    /**
     * Get formula latest release info.
     *
     * @param FormulaModel $formula
     *
     * @return array
     */
    protected function watchdog(FormulaModel $formula)
    {
        // get the formula's checker
        $checker = $this->checker($formula);

        // check the formula has new release or not
        $latest = $checker->latest();

        // get formatted current formula version in database
        $current = $checker->version($formula->version);

        // if there is new release, set up variables
        if (Comparator::greaterThan($checker->version($latest), $current)) {
            return ['version' => $latest];
        }

        return [];
    }
}
