<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'amount', 'is_percentage', 'is_exclusive', 'vendor_id', 'max_count',   'start_date', 'end_date', 'status', 'additional_threshold', 'additional_amount',
        'created_by', 'updated_by',
    ];

    use SoftDeletes;

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

    public function products()
    {
        return $this->belongsToMany(\App\Product::class, 'coupon_product_pivots', 'coupon_id', 'product_id');
    }

}
