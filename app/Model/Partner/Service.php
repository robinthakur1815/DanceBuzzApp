<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'services';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'category_id',
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
     * Get the vendor_services for the Service.
     */
    public function vendorServices()
    {
        return $this->belongsToMany(\App\Vendor::class, 'vendor_service', 'service_id', 'vendor_id')->withPivot('isActive');
    }

    /**
     * Get the Category for the Service.
     */
    public function category()
    {
        return $this->belongsTo(\App\Category::class, 'category_id');
    }

    /**
     * Get the Vendors for the Service.
     */
    public function vendors()
    {
        return $this->belongsToMany(\App\Vendor::class, 'vendor_service', 'service_id', 'vendor_id')->withPivot('isActive');
    }
}
