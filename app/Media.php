<?php

namespace App;

// use Illuminate\Database\Eloquent\SoftDeletes;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    // use SoftDeletes;
    // public function media()
    // {
    //     return $this->morphTo();
    // }

    protected $table = 'media';

    protected $fillable = [
        'url', 'created_by', 'mime_type', 'name', 'size', 'media_type', 'alt_text', 'title', 'description',
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

    public function blogs()
    {
        return $this->morphedByMany(\App\Blog::class, 'mediables');
    }

    public function mediables()
    {
        return $this->hasMany(\App\Mediables::class, 'media_id');
    }

    // public function mediable()
    // {
    //     return $this->morphTo();
    // }
}
