<?php

namespace App\Checkers;

class Phar extends Github
{
    /**
     * Github phar archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://github.com/%s/releases/download/%s/%s.phar';

    /**
     * Get the archive url.
     *
     * @return string
     */
    protected function archiveUrl()
    {
        return sprintf($this->archiveUrl, $this->repo(), $this->version, $this->filename());
    }

    /**
     * Get archive filename.
     *
     * @return string
     */
    protected function filename()
    {
        $name = $this->formula->getAttribute('name');

        return substr($name, strrpos($name, '/') + 1);
    }
}
