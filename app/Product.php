<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'description', 'sku', 'stock',   'merchant_code', 'vendor_id',  'status', 'categories', 'tags', 'collection_id', 'created_by', 'updated_by',
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

    public function orders()
    {
        return $this->hasMany(\App\Order::class);
    }

    public function prices()
    {
        return $this->hasMany(\App\ProductPrice::class)->withTrashed();
    }

    public function mediables()
    {
        return $this->morphMany(\App\Mediables::class, 'mediable');
    }

    public function productReviews()
    {
        return $this->hasMany(\App\ProductReviews::class, 'product_id', 'id');
    }

    public function coupons()
    {
        return $this->belongsToMany(\App\Coupon::class, 'coupon_product_pivots');
    }

    /**
     * The packages that belong to the offer.
     */
    public function packages()
    {
        return $this->belongsToMany(\App\ProductPrice::class, 'product_prices_product', 'product_id', 'product_price_id')
            ->withTimestamps()->withPivot('is_active');
    }

    /**
     * The packages that belong to the offer.
     */
    public function activePackages()
    {
        return $this->belongsToMany(\App\ProductPrice::class, 'product_prices_product', 'product_id', 'product_price_id')
            ->wherePivot('is_active', true)
            ->withTimestamps()->withPivot('is_active');
    }

    // public function coupons()
    // {
    //     return $this->belongsToMany('App\Coupon', 'coupon_product_pivots');
    // }

    public function collection()
    {
        return $this->belongsTo(\App\Collection::class)->withTrashed();
    }
}
