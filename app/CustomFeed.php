<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class CustomFeed extends Model
{
    protected $fillable = [
        'title', 'slug', 'description', 'excerpt', 'url', 'created_by', 'updated_by', 'type', 'saved_content'
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

    public function medias()
    {
        return $this->morphToMany(\App\Media::class, 'mediable');
    }

    public function mediables()
    {
        return $this->morphMany(\App\Mediables::class, 'mediable');
    }

    public function feeds()
    {
        return $this->morphToMany(\App\Feed::class, 'feedable')->latest();
    }
}
