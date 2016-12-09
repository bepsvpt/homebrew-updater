<?php

namespace App\Checkers;

use League\Uri\Schemes\Http;

class Github extends Checker
{
    /**
     * Github tag api.
     *
     * @reference https://developer.github.com/v3/git/tags/#get-a-tag
     *
     * @var string
     */
    protected $tagApi = 'https://api.github.com/repos/%s/tags';

    /**
     * Github tag tar archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://github.com/%s/archive/%s.tar.gz';

    /**
     * Transform version name if need.
     *
     * @param string $version
     *
     * @return string
     */
    public function version($version)
    {
        switch ($this->formula->getAttribute('name')) {
            case 'homebrew/php/phpmyadmin':
                return str_replace('_', '.', substr($version, 8));
            default:
                return parent::version($version);
        }
    }

    /**
     * Get repository latest version.
     *
     * @return string|null
     */
    public function latest()
    {
        $url = sprintf($this->tagApi, $this->repo());

        $content = $this->fetch($url);

        $tags = json_decode($content, true);

        if (empty($tags)) {
            return null;
        }

        return $this->version = array_first($tags)['name'];
    }

    /**
     * Get latest archive info.
     *
     * @return array
     */
    public function archive()
    {
        if (is_null($this->version)) {
            throw new \InvalidArgumentException('Version can not be null.');
        }

        $url = $this->archiveUrl();

        $content = $this->fetch($url);

        $hash = sprintf('%s:%s', $this->hash, hash($this->hash, $content));

        return compact('url', 'hash');
    }

    /**
     * Get archive url.
     *
     * @return string
     */
    protected function archiveUrl()
    {
        return sprintf($this->archiveUrl, $this->repo(), $this->version);
    }

    /**
     * Get repository name.
     *
     * @return string
     */
    protected function repo()
    {
        $url = Http::createFromString($this->formula->getAttribute('url'));

        return substr($url->getPath(), 1);
    }
}
