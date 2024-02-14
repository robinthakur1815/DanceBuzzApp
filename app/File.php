<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    protected $fillable = [
        'uuid', 'created_by', 'mime_type', 'filename', 'size', 'alt_text', 'title', 'description',
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
}
