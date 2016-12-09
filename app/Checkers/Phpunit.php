<?php

namespace App\Checkers;

class Phpunit extends Github
{
    /**
     * PHPUnit archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://phar.phpunit.de/phpunit-%s.phar';

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
