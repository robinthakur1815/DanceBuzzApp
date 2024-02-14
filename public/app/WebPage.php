<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebPage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'content', 'created_by', 'updated_by',
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

    public function sections()
    {
        return $this->belongsToMany(\App\WebSection::class, 'web_page_sections')->orderBy('web_page_sections.sequence');
    }

    public function sectionSequence()
    {
        return $this->hasMany(\App\WebPageSection::class)->orderBy('sequence');
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

    public function seo()
    {
        return $this->morphOne(\App\Seo::class, 'model');
    }
}
