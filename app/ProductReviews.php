<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductReviews extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'product_id', 'purchaser_id', 'review',
        'rating', 'purchaser_name', 'collection_id',
        'purchaser_avatar', 'review_status', 'approved_at',
        'approved_by',
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

    public function collection()
    {
        return $this->belongsTo(\App\Collection::class, 'collection_id', 'id')->withTrashed();
    }

    public function approvedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'approved_by');
    }
}
