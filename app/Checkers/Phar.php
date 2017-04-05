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
        // fill url directives
        // e.g. https://github.com/%s/releases/download/%s/%s.phar
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

        // if formula's name is homebrew/xxx/zzz, we only need `zzz`
        if (false === ($pos = strrpos($name, '/'))) {
            return $name;
        }

        return substr($name, $pos + 1);
    }
}
