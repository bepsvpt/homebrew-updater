<?php

namespace App\Checkers;

class PharFilenameWithVersion extends Phar
{
    /**
     * Get archive filename.
     *
     * @return string
     */
    protected function filename()
    {
        return sprintf('%s-%s', parent::filename(), $this->version);
    }
}
