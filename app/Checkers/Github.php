<?php

namespace App\Checkers;

use App\Models\Commit;
use App\Models\Formula;
use Carbon\Carbon;
use Github\Client as GithubClient;
use Github\ResultPager;
use Illuminate\Support\Arr;

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

        $this->github->authenticate(
            config('services.github.token'),
            'http_token'
        );

        $this->paginator = new ResultPager($this->github);
    }

    /**
     * Get repository latest version.
     * Return null when no release.
     *
     * @return string|null
     */
    public function latest(): ?string
    {
        // if version property has been set, return it immediately
        if (!is_null($this->version)) {
            return $this->version;
        }

        // if tags are empty, there is no release yet
        if (empty($tags = $this->tags())) {
            return null;
        }

        // transform the version and return it
        return $this->version = $this->version(Arr::first($tags)['name']);
    }

    /**
     * Get repository tags.
     *
     * @return array
     */
    protected function tags(): array
    {
        // get formula tags
        $tags = $this->paginator->fetchAll(
            $this->github->repos(),
            'tags',
            explode('/', $this->formula->repo)
        );

        // add date field to repo tags
        $this->withDate($tags);

        // sort tags by date field
        usort($tags, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $tags;
    }

    /**
     * Because repo tags do not contain time info, we
     * need to add it manually from related commit.
     *
     * @param array $tags
     *
     * @return void
     */
    protected function withDate(array &$tags): void
    {
        foreach ($tags as &$tag) {
            // get tag commit sha
            $sha = $tag['commit']['sha'];

            // get commit info from database or use github api
            $commit = $this->formula->commits()->find($sha) ?: $this->commit($sha);

            // add date info to repo tag
            $tag['date'] = $commit->committed_at->toDateTimeString();

            // remove unused fields
            unset(
                $tag['zipball_url'],
                $tag['tarball_url'],
                $tag['commit'],
                $tag['node_id']
            );
        }
    }

    /**
     * Get commit info and save to database.
     *
     * @param string $sha
     *
     * @return Commit
     */
    protected function commit(string $sha): Commit
    {
        // split repo to owner and name
        $arguments = explode('/', $this->formula->repo);

        // push sha to the end of the array
        array_push($arguments, $sha);

        // get commit info
        $commit = $this->github->repos()->commits()->show(...$arguments);

        // retrieve date from commit
        $date = $commit['commit']['author']['date'];

        // convert date to carbon instance
        $committedAt = Carbon::parse($date, config('app.timezone'));

        /** @noinspection PhpIncompatibleReturnTypeInspection */
        // save and return eloquent model
        return $this->formula->commits()->create([
            'sha' => $sha,
            'committed_at' => $committedAt,
        ]);
    }

    /**
     * Transform version name if need.
     *
     * @param string $version
     *
     * @return string
     */
    public function version($version): string
    {
        switch ($this->formula->name) {
            // RELEASE_4_7_0 → 4.7.0
            case 'homebrew/core/phpmyadmin':
                return str_replace(['RELEASE_', '_'], ['', '.'], $version);
        }

        return parent::version($version);
    }
}
