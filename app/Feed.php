<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    protected $fillable = [
        'feedable_type', 'feedable_id', 'status', 'sequence', 'is_sticky', 'created_by', 'updated_by', 'is_partner', 'is_publish',
    ];

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function feedable()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\User::class, 'updated_by');
    }

    public function likes()
    {
        return $this->morphMany(\App\Like::class, 'likable');
    }

    public function comments()
    {
        return $this->morphMany(\App\Comment::class, 'commentable')->where('is_active', 1)->latest();
    }

    public function dynamicurls()
    {
        return $this->morphMany('App\DynamicUrl', 'dynamicurlable');
    }
}
