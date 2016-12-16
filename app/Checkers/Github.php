<?php

namespace App\Checkers;

use App\Models\Commit;
use App\Models\Formula;
use Carbon\Carbon;
use DB;
use Github\Client as GithubClient;
use Github\ResultPager;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Promise;
use League\Uri\Schemes\Http;
use Psr\Http\Message\ResponseInterface;

class Github extends Checker
{
    /**
     * Github tag tar archive url.
     *
     * @var string
     */
    protected $archiveUrl = 'https://github.com/%s/archive/%s.tar.gz';

    /**
     * @var GithubClient
     */
    protected $github;

    /**
     * @var Guzzle
     */
    protected $guzzle;

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

        $this->guzzle = new Guzzle(['headers' => $this->headers()]);
    }

    /**
     * Guzzle request headers.
     *
     * @return array
     */
    protected function headers()
    {
        return [
            'Authorization' => 'token '.config('services.github.token'),
            'Time-Zone' => config('app.timezone'),
        ];
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
            case 'homebrew/php/phpmyadmin':
                return str_replace('_', '.', substr($version, 8));
            default:
                return parent::version($version);
        }
    }

    /**
     * Repository latest version.
     *
     * @return string|null
     */
    public function latest()
    {
        if (! is_null($this->version)) {
            return $this->version;
        }

        if (empty($tags = $this->tags())) {
            return null;
        }

        return $this->version = array_first($tags)['name'];
    }

    /**
     * Repository tags.
     *
     * @return array
     */
    protected function tags()
    {
        $tags = (new ResultPager($this->github))
            ->fetchAll($this->github->repos(), 'tags', $this->repo(true));

        $this->appendDate($tags);

        usort($tags, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        return $tags;
    }

    /**
     * Append date information to tags.
     *
     * @param array $tags
     *
     * @return void
     */
    protected function appendDate(array &$tags)
    {
        $dates = DB::table(Commit::getTableName())
            ->where($this->formula->commits()->getPlainForeignKey(), $this->formula->getKey())
            ->whereIn('sha', array_column(array_column($tags, 'commit'), 'sha'))
            ->get()
            ->pluck('committed_at', 'sha')
            ->toArray();

        foreach ($tags as $tag) {
            if (! isset($dates[$tag['commit']['sha']])) {
                $urls[$tag['commit']['sha']] = $tag['commit']['url'];
            }
        }

        $dates = array_merge($dates, $this->dates($urls ?? []));

        foreach ($tags as &$tag) {
            $tag['date'] = $dates[$tag['commit']['sha']];
        }
    }

    /**
     * Commits date information.
     *
     * @param array $urls
     *
     * @return array
     */
    protected function dates(array $urls)
    {
        $dates = [];

        foreach (array_chunk($urls, 15, true) as $chunk) {
            $promises = array_map([$this->guzzle, 'getAsync'], $chunk);

            $dates += array_map([$this, 'parse'], Promise\unwrap($promises));
        }

        $this->insert($dates);

        return $dates;
    }

    /**
     * Parse commit date from response.
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    protected function parse(ResponseInterface $response)
    {
        $content = $response->getBody()->getContents();

        $commit = json_decode($content, true);

        $date = $commit['commit']['author']['date'];

        return Carbon::parse($date, config('app.timezone'))->toDateTimeString();
    }

    /**
     * Insert new commits to database.
     *
     * @param array $commits
     *
     * @return void
     */
    protected function insert(array $commits)
    {
        $foreignKey = $this->formula->commits()->getPlainForeignKey();

        foreach ($commits as $sha => $date) {
            $records[] = [
                $foreignKey => $this->formula->getKey(),
                'sha' => $sha,
                'committed_at' => $date,
            ];
        }

        foreach (array_chunk($records ?? [], 30) as $chunk) {
            DB::table(Commit::getTableName())->insert($chunk);
        }
    }

    /**
     * Latest archive information.
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
     * @param bool $explode
     *
     * @return array|string
     */
    protected function repo($explode = false)
    {
        $url = Http::createFromString($this->formula->getAttribute('url'));

        $repo = substr($url->getPath(), 1);

        return $explode
            ? array_combine(['user', 'name'], explode('/', $repo))
            : $repo;
    }
}
