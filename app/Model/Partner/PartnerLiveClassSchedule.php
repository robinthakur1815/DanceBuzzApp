<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerLiveClassSchedule extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'live_class_schedules';

    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'live_class_id', 'vendor_id', 'start_date_time', 'status', 'created_by', 'updated_by',
    ];

    protected $dates = ['start_date_time'];

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

    public function vendorLiveClass()
    {
        return $this->belongsTo(\App\Model\Partner\PartnerLiveClass::class, 'live_class_id');
    }

    
}
