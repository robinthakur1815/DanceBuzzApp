<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductPrice extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'color', 'price', 'unit', 'sequence', 'product_id', 'vendor_id', 'status', 'name', 'created_by', 'updated_by', 'discount_id',
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

    public function products()
    {
        return $this->belongsTo(\App\Product::class, 'product_id', 'id');
    }

    public function discounts()
    {
        return $this->belongsTo(\App\Discount::class, 'discount_id', 'id');
    }

    public function medias()
    {
        return $this->morphToMany(\App\Media::class, 'mediable');
    }

    public function mediables()
    {
        return $this->morphMany(\App\Mediables::class, 'mediable');
    }
}
