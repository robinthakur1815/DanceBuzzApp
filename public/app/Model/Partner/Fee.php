<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'fees';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'amount',  'is_publish', 'validity', 'vendor_id', 'owner_id', 'location_id', 'split_no', 'created_by', 'isActive',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        //
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
     * Get the student_registrations for the Fee.
     */
    public function studentRegistrations()
    {
        return $this->hasMany('App\student_registration');
    }

    /**
     * Get the Vendor for the Fee.
     */
    public function vendor()
    {
        return $this->belongsTo(\App\Vendor::class, 'vendor_id');
    }
}
