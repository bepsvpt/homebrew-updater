<?php

namespace App\Checkers;

class Phploc extends External
{
    /**
     * Phploc archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://phar.phpunit.de/phploc-%s.phar';
}
