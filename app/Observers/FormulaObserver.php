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

            if ($this->shouldCommit($formula)) {
                dispatch(new CommitGit($formula));
            }
        }
    }

    /**
     * Check the release should be committed.
     *
     * @param Formula $formula
     *
     * @return bool
     */
    protected function shouldCommit(Formula $formula)
    {
        if (str_contains($formula->getAttribute('version'), ['beta', 'alpha', 'dev'])) {
            return false;
        } elseif (! $formula->getAttribute('git')['path']) {
            return false;
        }

        return true;
    }
}
