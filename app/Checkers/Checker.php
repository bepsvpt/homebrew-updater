<?php

namespace App\Checkers;

use App\Models\Formula;
use Illuminate\Support\Str;

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
     * @param string $version
     *
     * @return string
     */
    public function version($version): string
    {
        // v1.2.3 → 1.2.3
        if (Str::startsWith($version, ['v'])) {
            return substr($version, 1);
        }

        return $version;
    }

    /**
     * Get repository latest version.
     * Return null when no release.
     *
     * @return string|null
     */
    abstract public function latest(): ?string;
}
