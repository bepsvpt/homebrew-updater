<?php

namespace App\Observers;

use App\Models\Formula;
use App\Jobs\CommitGit;
use App\Notifications\FormulaReleased;

class FormulaObserver
{
    /**
     * Listen to the Formula updated event.
     *
     * @param Formula $formula
     *
     * @return void
     */
    public function updated(Formula $formula)
    {
        $dirties = $formula->getDirty();

        if (isset($dirties['version'])) {
            $formula->notify(new FormulaReleased);

            if ($formula->getAttribute('git')['path']) {
                dispatch(new CommitGit($formula));
            }
        }
    }
}
