<?php

namespace App\Model\Partner;

use App\Model\PartnerCollection;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class VendorClass extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'description', 'start_date', 'end_date', 'start_time', 'end_time', 'status',
        'frequencey_per_month', 'vendor_id', 'owner_id', 'location_id',
        'service_id', 'created_by', 'updated_by', 'is_publish',
    ];

    protected $dates = [
        'start_date', 'end_date', 'start_time', 'end_time',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        //
    ];

    protected $connection = 'partner_mysql';

    protected $table = 'vendor_classes';

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
     * The packages that belong to the offer.
     */
    public function teachers()
    {
        $database = config('database.connections.mysql.database');

        return $this->belongsToMany(\App\User::class, "$database.class_teacher", 'vendorclass_id', 'user_id')
                                ->withTimestamps()->withPivot('isActive');
    }

    /**
     * The classes that belong to the package.
     */
    public function packages()
    {
        return $this->belongsToMany('App\Package', 'class_fee', 'vendor_class_id', 'package_id')
                                ->withTimestamps()->withPivot('isActive');
    }

    /**
     * Get the Location for the VendorClass.
     */
    public function location()
    {
        return $this->belongsTo(\App\Model\Partner\Location::class, 'location_id');
    }

    /**
     * Get the Student Registration for the VendorClass.
     */
    public function students()
    {
        return $this->hasMany('App\StudentRegistration', 'vendorclass_id')->latest();
    }

    /**
     * Get the attendances for the VendorClass.
     */
    public function attendances()
    {
        return $this->hasMany('App\StudentAttendance', 'vendorclass_id')->latest();
    }

      /**
     * Get the clollection for the VendorClass.
     */
    public function clollection()
    {
        return $this->hasOne(PartnerCollection::class, 'vendorclass_id');
    }

    /**
     * Get the owner for the VendorClass.
     */
    public function owner()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'owner_id');
    }

    /**
     * Get the Service for the VendorClass.
     */
    public function service()
    {
        return $this->belongsTo('App\Service', 'service_id');
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
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'created_by');
    }
}
