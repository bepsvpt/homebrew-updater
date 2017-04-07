<?php

namespace App\Checkers;

use App\Models\Formula;

abstract class Checker
{
    /**
     * @var Formula
     */
    protected $formula;

    /**
     * @var string|null
     */
    protected $version;

    /**
     * Constructor.
     *
     * @param Formula $formula
     */
    public function __construct(Formula $formula)
    {
        $this->formula = $formula;
    }

    /**
     * Transform version name if necessary.
     *
     * @param string|null $version
     *
     * @return string
     */
    public function version($version)
    {
        // v1.2.3 → 1.2.3
        if (starts_with($version, ['v'])) {
            return substr($version, 1);
        }

        return $version;
    }

    /**
     * Get repository latest version. If there is no release, return null.
     *
     * @return string|null
     */
    abstract public function latest();
}
