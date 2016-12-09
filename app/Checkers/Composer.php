<?php

namespace App\Checkers;

class Composer extends Github
{
    /**
     * Github tag tar archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://github.com/%s/releases/download/%s/composer.phar';
}
