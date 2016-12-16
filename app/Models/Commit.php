<?php

namespace App\Models;

class Commit extends Model
{
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'sha';

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

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
    protected $dates = ['committed_at'];

    /**
     * Get the formula that owns the commit.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function formula()
    {
        return $this->belongsTo(Formula::class);
    }
}
