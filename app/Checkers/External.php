<?php

namespace App\Checkers;

class External extends Github
{
    /**
     * Get the archive url.
     *
     * @return string
     */
    protected function archiveUrl()
    {
        return sprintf($this->archiveUrl, $this->version);
    }
}
