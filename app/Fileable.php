<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Fileable extends Model
{
    protected $table = 'fileables';

    protected $fillable = ['file_id', 'mediable_type', 'mediable_id', 'created_by', 'updated_by', 'name'];

    public function file()
    {
        return $this->belongsTo(\App\File::class, 'file_id');
    }

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
