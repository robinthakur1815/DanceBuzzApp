<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpamReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reportable_type', 'reportable_id', 'status', 'description', 'created_by', 'updated_by',
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

    /**
     * Get the owning imageable model.
     */
    public function reportable()
    {
        return $this->morphTo();
    }

    public function comment()
    {
        return $this->belongsTo(\App\Comment::class, 'reportable_id');
    }

    public function createdBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'updated_by');
    }
}
