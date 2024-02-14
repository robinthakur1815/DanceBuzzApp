<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationGroup extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';
    protected $table = 'notification_groups';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'group_type', 'notification_id', 'group_name', 'type', 'created_by'
    ];

}
