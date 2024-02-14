<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'user_profiles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'reference_code', 'enthu_points', 'gender',
        'provider_id', 'provider_type', 'register_type', 'created_by',
        'updated_by', 'activated_by',
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

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\User::class, 'updated_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function activatedBy()
    {
        return $this->belongsTo(\App\User::class, 'activated_by');
    }
}
