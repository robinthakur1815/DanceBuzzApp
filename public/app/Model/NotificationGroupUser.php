<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificationGroupUser extends Model
{
    use  SoftDeletes;

    protected $connection = 'mysql2';
    protected $table = 'notification_group_users';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group_id', 'user_role', 'user_id'
    ];
}
