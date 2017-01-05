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
        'git' => 'array',
    ];

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
