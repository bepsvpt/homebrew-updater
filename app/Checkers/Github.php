<?php

namespace App\Checkers;

use App\Models\Formula;
use Carbon\Carbon;
use Github\Client as GithubClient;
use Github\ResultPager;

class Github extends Checker
{
    /**
     * GitHub client.
     *
     * @var GithubClient
     */
    protected $github;

    /**
     * Github pagination.
     *
     * @var ResultPager
     */
    protected $paginator;

    /**
     * Constructor.
     *
     * @param Formula $formula
     */
    public function __construct(Formula $formula)
    {
        parent::__construct($formula);

        $this->github = new GithubClient;
        $this->github->authenticate(config('services.github.token'), 'http_token');

        $this->paginator = new ResultPager($this->github);
    }

    /**
     * Get repository latest version. If there is no release, return null.
     *
     * @return string|null
     */
    public function latest()
    {
        // if version property has been set, return it immediately
        if (! is_null($this->version)) {
            return $this->version;
        }

        // if tags are empty, there is no release yet
        if (empty($tags = $this->tags())) {
            return null;
        }

        // transform the version and return it
        return $this->version = $this->version(array_first($tags)['name']);
    }

    /**
     * Get repository tags.
     *
     * @return array
     */
    protected function tags()
    {
        // get formula tags
        $tags = $this->paginator->fetchAll(
            $this->github->repos(),
            'tags',
            explode('/', $this->formula->getAttribute('repo'))
        );

        // add date field to repo tags
        $tags = $this->withDate($tags);

        // sort tags by date field
        usort($tags, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        // return tags
        return $tags;
    }

    /**
     * Add date info to repo tags.
     *
     * @param array $tags
     *
     * @return array
     */
    protected function withDate(array $tags)
    {
        return array_map(function ($tag) {
            // get commit info from database or use github api
            $commit = $this->formula->commits()->find($tag['commit']['sha']) ?: $this->commit($tag['commit']['sha']);

            // add date info to repo tag
            $tag['date'] = $commit->getAttribute('committed_at')->toDateTimeString();

            return $tag;
        }, $tags);
    }

    /**
     * Get commit info and save to database.
     *
     * @param string $sha
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function commit($sha)
    {
        // split repo to owner and name
        $arguments = explode('/', $this->formula->getAttribute('repo'));

        // push sha to the end of the array
        array_push($arguments, $sha);

        // get commit info
        $commit = $this->github->repos()->commits()->show(...$arguments);

        // save to database and return Eloquent instance
        return $this->formula->commits()->create([
            'sha' => $sha,
            'committed_at' => Carbon::parse($commit['commit']['author']['date'], config('app.timezone')),
        ]);
    }

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
            // RELEASE_4_7_0 → 4.7.0
            case 'homebrew/php/phpmyadmin':
                return str_replace(['RELEASE_', '_'], ['', '.'], $version);

            default:
                return parent::version($version);
        }
    }
}
