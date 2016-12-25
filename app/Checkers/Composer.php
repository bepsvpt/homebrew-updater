<?php

namespace App\Checkers;

class Composer extends External
{
    /**
     * Composer archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://getcomposer.org/download/%s/composer.phar';
}
