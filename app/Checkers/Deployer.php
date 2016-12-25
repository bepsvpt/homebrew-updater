<?php

namespace App\Checkers;

class Deployer extends External
{
    /**
     * Deployer archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://deployer.org/releases/%s/deployer.phar';
}
