<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class CategoryGroup extends Model
{
    protected $fillable = [
        'name', 'slug', 'updated_by', 'created_by', 'collection_type', 'deleted_at',
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

    public function categories()
    {
        return $this->belongsToMany(\App\Category::class, 'category_group_pivots');
    }

    public function categoryPivots()
    {
        return $this->hasMany(\App\CategoryGroupPivot::class);
    }
}
