<?php

namespace App\Checkers;

class PharVersionPrefixV extends Phar
{
    /**
     * Github phar archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://github.com/%s/releases/download/v%s/%s.phar';
}
