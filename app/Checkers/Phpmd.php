<?php

namespace App\Checkers;

class Phpmd extends External
{
    /**
     * Phpmd archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'http://static.phpmd.org/php/%s/phpmd.phar';
}
