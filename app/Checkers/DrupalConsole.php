<?php

namespace App\Checkers;

class DrupalConsole extends Phar
{
    /**
     * Get archive filename.
     *
     * @return string
     */
    protected function filename()
    {
        return 'drupal';
    }
}
