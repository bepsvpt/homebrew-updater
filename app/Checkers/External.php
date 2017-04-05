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
        // fill url directives
        // e.g. https://getcomposer.org/download/%s/composer.phar
        return sprintf($this->archiveUrl, $this->version);
    }
}
