<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;

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
            '{name}' => array_last(explode('/', $this->getAttribute('name'))),
            '{version}' => $version ?: $this->getAttribute('version'),
        ];

        return strtr($this->getAttribute('archive'), $pairs);
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
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function commits()
    {
        return $this->hasMany(Commit::class);
    }
}
