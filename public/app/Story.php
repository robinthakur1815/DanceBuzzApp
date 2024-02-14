<?php

namespace App;

use DateTimeInterface;
use App\Enums\CollectionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Story extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name', 'student_user_id', 'description', 'comments',
        'sub_category_id', 'category_id', 'campaign_id', 'meta',
        'created_by', 'updated_by', 'status', 'reason','is_shoppable',
    ];

    protected $casts = [

        'meta'      => 'array',
        'saved_content' => 'array',
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

    public function files()
    {
        return $this->morphToMany(\App\File::class, 'fileables');
    }

    public function fileables()
    {
        return $this->morphMany(\App\Fileable::class, 'mediable');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'student_user_id');
    }

    public function student()
    {
        return $this->belongsTo(\App\User::class, 'student_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function campaign()
    {
        return $this->belongsTo(\App\Collection::class, 'campaign_id');
    }

    public function category()
    {
        return $this->belongsTo(\App\Collection::class, 'category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(\App\Collection::class, 'sub_category_id');
    }
    public function campaigntype()
    {
        return $this->belongsTo(\App\Collection::class)->where('collection_type',CollectionType::campaignsType);
    }
}
