<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class VendorCategories extends Model
{
    protected $connection = 'partner_mysql';

    protected $table = 'categories';

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
