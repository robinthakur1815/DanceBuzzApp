<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class PartnerLiveClass extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'live_classes';

    public function vendorClass()
    {
        return $this->belongsTo(PartnerClass::class, 'vendor_class_id');
    }

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
