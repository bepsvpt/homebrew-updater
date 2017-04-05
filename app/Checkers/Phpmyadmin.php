<?php

namespace App\Checkers;

class Phpmyadmin extends Github
{
    /**
     * phpMyAdmin archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://files.phpmyadmin.net/phpMyAdmin/%s/phpMyAdmin-%s-all-languages.tar.gz';

    /**
     * Get the archive url.
     *
     * @return string
     */
    protected function archiveUrl()
    {
        // fill url directives
        // e.g. https://files.phpmyadmin.net/phpMyAdmin/%s/phpMyAdmin-%s-all-languages.tar.gz
        return sprintf($this->archiveUrl, $this->version, $this->version);
    }
}
