<?php

namespace App\Checkers;

class Composer extends Github
{
    /**
     * Composer archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://getcomposer.org/download/%s/composer.phar';

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
