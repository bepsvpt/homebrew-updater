<?php

namespace App\Checkers;

class Phing extends External
{
    /**
     * Phing archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://www.phing.info/get/phing-%s.phar';
}
