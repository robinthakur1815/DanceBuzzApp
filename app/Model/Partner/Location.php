<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'locations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vendor_id', 'user_id', 'contact_email', 'contact_phone1',
        'contact_phone2', 'address', 'city', 'zipcode',
        'latitude', 'longitude', 'state_id', 'isActive',
        'isVerified',
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
     * Get the VendorClasses for the Location.
     */
    public function vendorClasses()
    {
        return $this->hasMany('App\VendorClass');
    }

    /**
     * Get the student_registrations for the Location.
     */
    public function studentRegistrations()
    {
        return $this->hasMany('App\student_registration');
    }

    /**
     * Get the State for the Location.
     */
    public function state()
    {
        return $this->belongsTo('App\State');
    }

    /**
     * Get the Vendor for the Location.
     */
    public function vendor()
    {
        return $this->belongsTo(\App\Vendor::class);
    }

    /**
     * Get the user full name.
     */
    public function getFullAddressAttribute()
    {
        $address = $this->address;
        if ($this->address) {
            $address = $this->address.', '.$this->city.', '.$this->zipcode.', '.$this->state->name;
        }

        return $address;
    }
}
