<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'commentable_type', 'commentable_id', 'parent_comment_id', 'role_id', 'comment', 'is_active', 'created_by', 'updated_by',
        'collection_type',
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

    public function collections()
    {
        return $this->morphMany(\App\Collection::class, 'collection_id');
    }

    public function medias()
    {
        return $this->morphToMany(\App\Collection::class, 'mediable');
    }

    public function spams()
    {
        return $this->morphMany(\App\SpamReport::class, 'reportable')->latest();
    }

    public function spamreports()
    {
        return $this->hasMany(\App\SpamReport::class, 'reportable_id')->where('reportable_type', self::class);
    }

    // public function user()
    // {
    //     return $this->hasOne('App\User', 'id' ,'created_by');
    // }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
