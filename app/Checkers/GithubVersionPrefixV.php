<?php

namespace App\Checkers;

class GithubVersionPrefixV extends Github
{
    /**
     * Github tag tar archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://github.com/%s/archive/v%s.tar.gz';
}
