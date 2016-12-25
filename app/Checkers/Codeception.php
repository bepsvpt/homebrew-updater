<?php

namespace App\Checkers;

class Codeception extends External
{
    /**
     * Codeception archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'http://codeception.com/releases/%s/codecept.phar';
}
