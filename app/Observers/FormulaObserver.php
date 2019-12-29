<?php

namespace App\Observers;

use App\Models\Formula;
use App\Jobs\CommitGit;
use App\Notifications\FormulaReleased;
use Illuminate\Support\Str;

class FormulaObserver
{
    /**
     * Listen to the Formula updated event.
     *
     * @param Formula $formula
     *
     * @return void
     */
    public function updated(Formula $formula): void
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
    protected function shouldCommit(Formula $formula): bool
    {
        $ignores = ['rc', 'beta', 'alpha', 'dev', 'pre'];

        // when there is no repo path or it is non
        // stable release, we just ignore it.
        if (Str::contains(mb_strtolower($formula->version), $ignores)) {
            return false;
        } elseif (!$formula->git['path']) {
            return false;
        }

        return true;
    }
}
