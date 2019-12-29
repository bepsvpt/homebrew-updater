<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;

/**
 * @property string $name
 * @property string $repo
 * @property string $checker
 * @property array|null $git
 * @property string|null $version
 * @property string|null $archive
 * @property string $archive_url
 * @property string|null $pull_request
 * @property boolean $enable
 * @property Carbon $checked_at
 * @property array|null $revision
 * @property array|null $dependent
 * @property Collection|Commit[] $commits
 */
class Formula extends Model
{
    use Notifiable;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['checked_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'enable' => 'boolean',
        'git' => 'array',
        'revision' => 'array',
        'dependent' => 'array',
    ];

    /**
     * Get the formula's archive url.
     *
     * @param null|string $version
     *
     * @return string
     */
    public function getArchiveUrlAttribute($version = null)
    {
        $pairs = [
            '{name}' => Arr::last(explode('/', $this->name)),
            '{version}' => $version ?: $this->version,
        ];

        return strtr($this->archive, $pairs);
    }

    /**
     * Route notifications for the Slack channel.
     *
     * @return string
     */
    public function routeNotificationForSlack()
    {
        return config('services.slack.url');
    }

    /**
     * Get the commits for the formula.
     *
     * @return HasMany
     */
    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class);
    }
}
