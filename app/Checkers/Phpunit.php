<?php

namespace App\Checkers;

class Phpunit extends External
{
    /**
     * PHPUnit archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://phar.phpunit.de/phpunit-%s.phar';
}
