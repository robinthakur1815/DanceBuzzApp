<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name', 'slug', 'updated_by', 'created_by', 'collection_type', 'parent_id', 'is_featured', 'is_user_defined',
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

    public function createdBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'updated_by');
    }

    public function parentTag()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function childTags()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function tagGroups()
    {
        return $this->belongsToMany(\App\TagGroup::class, 'tag_group_pivots');
    }

    public function collections()
    {
        return $this->belongsToMany(\App\Collection::class, 'tag_collection_pivots', 'tag_id', 'collection_id');
    }

    public function publishedCollections()
    {
        return $this->belongsToMany(\App\Collection::class, 'tag_collection_pivots', 'tag_id', 'collection_id')->whereNotNull('published_content');
    }

    public function collectionPivot()
    {
        return $this->hasMany(\App\TagCollectionPivot::class);
    }
}
