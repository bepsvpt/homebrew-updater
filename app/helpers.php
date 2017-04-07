<?php

if (! function_exists('fetch')) {
    /**
     * Send a http request and return response body.
     *
     * @param string $url
     *
     * @return string
     */
    function fetch($url)
    {
        return (new GuzzleHttp\Client())
            ->get($url)
            ->getBody()
            ->getContents();
    }
}

if (! function_exists('hash_remote')) {
    /**
     * Hash remote content.
     *
     * @param string $algo
     * @param string $url
     *
     * @return string
     */
    function hash_remote($algo, $url)
    {
        return hash($algo, fetch($url));
    }
}
