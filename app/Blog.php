<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Blog extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'title', 'published_content', 'saved_content', 'created_by',
        'updated_by', 'published_by', 'published_at', 'deleted_at',
        'status', 'collection_type', 'is_featured', 'is_recommended', 'categories',
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

    public function seos()
    {
        return $this->morphMany(\App\Seo::class, 'model');
    }

    public function medias()
    {
        return $this->morphToMany(\App\Media::class, 'mediable');
    }

    public function mediables()
    {
        return $this->morphMany(\App\Mediables::class, 'mediable');
    }

    public function createdBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'updated_by');
    }

    public function publishedBy()
    {
        return $this->belongsTo(\App\User::class, 'published_by');
    }

    public function versions()
    {
        return $this->hasMany('App\BlogVersion');
    }

    public function versionNames()
    {
        return $this->versions()->select('id', 'version');
    }
}
