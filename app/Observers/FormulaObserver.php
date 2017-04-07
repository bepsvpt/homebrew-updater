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
        // get formula modified fields
        $dirties = $formula->getDirty();

        // when `version` is modified, there is new release
        if (isset($dirties['version'])) {
            // if the formula has local git repo, we should commit it
            if ($this->shouldCommit($formula)) {
                dispatch(new CommitGit($formula));
            }

            // send notification
            $formula->notify(new FormulaReleased);
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
        // when there is no repo path or it is non production release, we will not commit it
        if (str_contains(mb_strtolower($formula->getAttribute('version')), ['rc', 'beta', 'alpha', 'dev'])) {
            return false;
        } elseif (! $formula->getAttribute('git')['path']) {
            return false;
        }

        return true;
    }
}
