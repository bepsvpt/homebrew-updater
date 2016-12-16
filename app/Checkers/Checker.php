<?php

namespace App\Checkers;

use App\Models\Formula;
use GuzzleHttp\Client;

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
     * Hash Algorithm.
     *
     * @var string
     */
    protected $hash = 'sha256';

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
     * Fetch the http request.
     *
     * @param string $url
     *
     * @return string
     */
    protected function fetch($url)
    {
        return (new Client())
            ->get($url)
            ->getBody()
            ->getContents();
    }

    /**
     * Transform version name if need.
     *
     * @param string|null $version
     *
     * @return string
     */
    public function version($version)
    {
        return $version;
    }

    /**
     * Get the repository latest version.
     *
     * @return string
     */
    abstract public function latest();

    /**
     * Get the latest archive info.
     *
     * @return array
     */
    abstract public function archive();
}
