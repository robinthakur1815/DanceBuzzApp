<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = [
        'likable_type', 'likable_id', 'is_active', 'created_by', 'updated_by', 'is_liked',
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

    public function users()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
