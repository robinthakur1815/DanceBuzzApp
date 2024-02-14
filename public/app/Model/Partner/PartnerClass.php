<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class PartnerClass extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'vendor_classes';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'start_date', 'vendor_id', 'owner_id',
        'location_id', 'service_id', 'created_by', 'is_live',
        'is_publish', 'is_free', 'publish_status',
    ];

    protected $dates = [
        'start_date', 'end_date', 'start_time', 'end_time',
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
}
