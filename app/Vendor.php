<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'vendors';

    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'contact_first_name', 'contact_last_name', 'contact_email', 'contact_phone1',
        'contact_phone2', 'longitude', 'latitude', 'address', 'city', 'zipcode', 'state_id', 'approved_at',
        'rejection_reason', 'status', 'verification_renew_at', 'created_by', 'updated_by', 'approved_by',
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

    public function approvedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'approved_by');
    }

    /**
     * Get the Documents for the Vendor.
     */
    public function documents()
    {
        return $this->setConnection('partner_mysql')->hasMany(\App\Model\Partner\Document::class);
    }

    /**
     * Get the staff_vendors for the Vendor.
     */
    public function staffVendors()
    {
        return $this->setConnection('partner_mysql')->hasMany(\App\Model\Partner\StaffVendor::class, 'vendor_id');
    }

    /**
     * Get the Locations for the Vendor.
     */
    public function locations()
    {
        return $this->setConnection('partner_mysql')->hasMany(\App\Model\Partner\Location::class);
    }

    /**
     * Get the Fees for the Vendor.
     */
    public function fees()
    {
        return $this->setConnection('partner_mysql')->hasMany(\App\Model\Partner\Fee::class);
    }

    /**
     * Get the Discounts for the Vendor.
     */
    public function discounts()
    {
        return $this->setConnection('partner_mysql')->hasMany(\App\Model\Partner\Discount::class);
    }

    /**
     * Get the Payments for the Vendor.
     */
    public function payments()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\Payment');
    }

    /**
     * Get the Notifications for the Vendor.
     */
    public function notifications()
    {
        return $this->setConnection('partner_mysql')->hasMany('App\Notification');
    }

    /**
     * Get the Certificates for the Vendor.
     */
    public function certificates()
    {
        return $this->setConnection('partner_mysql')->hasMany(\App\Model\Partner\Certificate::class);
    }

    /**
     * Get the State for the Vendor.
     */
    public function state()
    {
        return $this->setConnection('partner_mysql')->belongsTo(\App\Model\Partner\State::class);
    }

    /**
     * Get the Services for the Vendor.
     */
    public function services()
    {
        return $this->setConnection('partner_mysql')->belongsToMany(\App\Model\Partner\Service::class, 'vendor_service');
    }

    /**
     * Get the Services for the Vendor.
     */
    public function active_services()
    {
        return $this->setConnection('partner_mysql')->belongsToMany(\App\Model\Partner\Service::class, 'vendor_service')
         ->withPivot(['isActive']);

        // if (count($this->services())) {
         //     return $this->belongsToMany('App\Service', 'vendor_service')
         //     ->withPivot(['isActive']);
         // }
         // return $this->belongsToMany('App\Service', 'vendor_service');
    }
}
