<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class StaffVendor extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'staff_vendor';

    protected $fillable = [
        'user_id', 'vendor_id', 'role_id', 'created_by', 'updated_by',
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

    public function vendor()
    {
        return $this->belongsTo(\App\Vendor::class, 'vendor_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\User::class, 'user_id');
    }

    public function role()
    {
        return $this->belongsTo(\App\Role::class, 'role_id');
    }
}
