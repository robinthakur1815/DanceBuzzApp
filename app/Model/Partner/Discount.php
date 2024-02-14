<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'discounts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'code', 'vendor_id', 'owner_id', 'value', 'isPercentage', 'isActive',
        'type', 'start_date', 'end_date', 'created_by', 'updated_by',
    ];

    protected $dates = [
        'start_date', 'end_date',
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
     * The discounts that belong to the package.
     */
    public function packages()
    {
        return $this->belongsToMany('App\Package', 'fee_discount', 'discount_id', 'package_id')
                                ->withTimestamps();
    }

    /**
     * Get the student_registrations for the Discount.
     */
    public function studentRegistrations()
    {
        return $this->hasMany('App\student_registration');
    }

    /**
     * Get the owner for the VendorClass.
     */
    public function owner()
    {
        return $this->belongsTo(\App\User::class, 'owner_id');
    }

    /**
     * Get the vendor for the VendorClass.
     */
    public function vendor()
    {
        return $this->belongsTo(\App\Vendor::class, 'vendor_id');
    }

    /**
     * Get the created_by for the VendorClass.
     */
    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
