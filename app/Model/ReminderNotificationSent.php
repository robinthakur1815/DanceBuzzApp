<?php

namespace App\Model;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class ReminderNotificationSent extends Model
{
    protected $fillable = ['user_id', 'collection_id'];
    protected $table = 'reminder_notification_sents';
    protected $connection = 'mysql2';

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
