<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log';
    protected $appends = ['causer_name'];

    public function causedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'causer_id');
    }

    public function getCauserNameAttribute()
    {
        $name = $this->causedBy->full_name;

        return $name;
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
