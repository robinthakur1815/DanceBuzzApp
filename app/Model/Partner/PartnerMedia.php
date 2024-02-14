<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class PartnerMedia extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'media';

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
