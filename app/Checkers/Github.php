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
            // RELEASE_4_7_0 → 4.7.0
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
        // if version property is set, just return it
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
     * Repository tags.
     *
     * @return array
     */
    protected function tags()
    {
        // get the formula's tags
        $tags = (new ResultPager($this->github))
            ->fetchAll($this->github->repos(), 'tags', $this->repo(true));

        // append date info to tags
        $this->appendDate($tags);

        // sort tags by date field
        usort($tags, function ($a, $b) {
            return $b['date'] <=> $a['date'];
        });

        // return tags
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
        // get commits that belong to the formula
        // extract `sha` and `committed_at` fields as an associative array
        $dates = DB::table(Commit::getTableName())
            ->where($this->formula->commits()->getPlainForeignKey(), $this->formula->getKey())
            ->whereIn('sha', array_column(array_column($tags, 'commit'), 'sha'))
            ->get()
            ->pluck('committed_at', 'sha')
            ->toArray();

        // get commits' url which are not in database
        foreach ($tags as $tag) {
            if (! isset($dates[$tag['commit']['sha']])) {
                $urls[$tag['commit']['sha']] = $tag['commit']['url'];
            }
        }

        // fetch new commits' info and merge to $dates
        $dates = array_merge($dates, $this->dates($urls ?? []));

        // update tags' date info
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

        // chunk urls to prevent fork error
        foreach (array_chunk($urls, 15, true) as $chunk) {
            // send asynchronous requests to get commit' info
            $promises = array_map([$this->guzzle, 'getAsync'], $chunk);

            // parse commits' date
            $dates += array_map([$this, 'parse'], Promise\unwrap($promises));
        }

        // insert commits' info to database
        $this->insert($dates);

        // return the commits' info
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
        // get http response content
        $content = $response->getBody()->getContents();

        // decode the data
        $commit = json_decode($content, true);

        // get commit's date
        $date = $commit['commit']['author']['date'];

        // use Carbon\Carbon to handle timezone and get date time string
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
        // get Commit foreign key
        $foreignKey = $this->formula->commits()->getPlainForeignKey();

        // set up commits' data for database insertion
        foreach ($commits as $sha => $date) {
            $records[] = [
                $foreignKey => $this->formula->getKey(),
                'sha' => $sha,
                'committed_at' => $date,
            ];
        }

        // chunk records to prevent sql error
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
        // version can not be null
        if (is_null($this->version)) {
            throw new \InvalidArgumentException('Version can not be null.');
        }

        // get archive url
        $url = $this->archiveUrl();

        // send request and get response content
        $content = $this->fetch($url);

        // calculate the hash
        $hash = sprintf('%s:%s', $this->hash, hash($this->hash, $content));

        // return all information as an associative array
        return compact('url', 'hash');
    }

    /**
     * Get archive url.
     *
     * @return string
     */
    protected function archiveUrl()
    {
        $repo = $this->repo(true);

        $pairs = [
            '{owner}' => $repo['user'],
            '{name}' => $repo['name'],
            '{version}' => $this->version,
        ];

        return strtr($this->formula->getAttribute('archive'), $pairs);
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
        // create Uri\Schemes from url string
        // e.g. https://github.com/phpmyadmin/phpmyadmin
        $url = Http::createFromString($this->formula->getAttribute('url'));

        // get phpmyadmin/phpmyadmin from https://github.com/phpmyadmin/phpmyadmin
        $repo = substr($url->getPath(), 1);

        // if explode is true, return as an associative array
        return $explode
            ? array_combine(['user', 'name'], explode('/', $repo))
            : $repo;
    }
}
