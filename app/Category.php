<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
    protected $table = 'categories';

    protected $fillable = [
        'name', 'slug', 'updated_by', 'created_by', 'collection_type', 'parent_id',
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

    public function parentCategory()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function createdBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'updated_by');
    }

    // public function allcollections()
    // {
    //     return $this->belongsToMany('App\Collection' , 'categories');
    // }

    public function childCategory()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function categoryGroups()
    {
        return $this->belongsToMany(\App\CategoryGroup::class, 'category_group_pivots');
    }

    public function mediables()
    {
        return $this->morphMany(\App\Mediables::class, 'mediable');
    }

    public function medias()
    {
        return $this->morphToMany(\App\Media::class, 'mediable');
    }

    public function collections()
    {
        return $this->belongsToMany(\App\Collection::class, 'category_collection_pivots', 'category_id', 'collection_id');
    }

    public function publishedCollections()
    {
        return $this->belongsToMany(\App\Collection::class, 'category_collection_pivots', 'category_id', 'collection_id')->whereNotNull('published_content');
    }

    public function collectionPivot()
    {
        return $this->hasMany(\App\CategoryCollectionPivot::class);
    }
}
