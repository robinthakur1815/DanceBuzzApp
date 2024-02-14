<?php

namespace App\Model;

use App\Traits\UsesUuid;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationModel extends Model
{
    use UsesUuid;
    use SoftDeletes;
    protected $fillable = ['read_at', 'type', 'created_by', 'app_type', 'data', 'notifiable_type', 'notifiable_id'];
    protected $table = 'notifications';
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
