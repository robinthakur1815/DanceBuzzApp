<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class EmailSend extends Model
{
    protected $fillable = [
        'user_id', 'email_type', 'email_count'
    ];
}