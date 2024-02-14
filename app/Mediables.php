<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Mediables extends Model
{
    // protected $table = "mediables";

    protected $fillable = ['media_id', 'model', 'model_type', 'model_id', 'created_by', 'name'];

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

    public function media()
    {
        return $this->belongsTo(\App\Media::class, 'media_id');
    }
}
