<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class WebSection extends Model
{
    use SoftDeletes;
    // use Searchable;
    use LogsActivity;

    protected $logAttributes = [
        'name', 'slug', 'title', 'heading', 'sub_heading', 'content', 'cta', 'collection_id', 'collection_count',
        'alignment_type', 'sequence', 'created_by', 'updated_by',
    ];
    protected static $ignoreChangedAttributes = ['created_at', 'updated_at'];
    protected static $logOnlyDirty = true;

    protected $fillable = [
        'name', 'slug', 'title', 'heading', 'sub_heading', 'content', 'cta', 'collection_id', 'collection_count',
        'alignment_type', 'sequence', 'created_by', 'updated_by',
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

    public function pages()
    {
        return $this->belongsToMany(\App\WebPage::class, 'web_page_sections');
    }

    public function media()
    {
        return $this->morphToMany(\App\Media::class, 'mediable');
    }

    public function mediables()
    {
        return $this->morphOne(\App\Mediables::class, 'mediable');
    }

    public function createdBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'updated_by');
    }

    public function collections()
    {
        return $this->belongsToMany(\App\Collection::class, 'web_section_collections');
    }
}
