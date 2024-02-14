<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discount extends Model
{
    protected $fillable = [
        'name', 'code', 'description', 'vendor_id', 'amount', 'is_percentage',   'max_count',   'start_date', 'end_date',
        'status', 'additional_threshold', 'additional_amount', 'created_by', 'updated_by',
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

    use SoftDeletes;

    public function medias()
    {
        return $this->morphToMany(\App\Media::class, 'mediable');
    }

    public function mediables()
    {
        return $this->morphMany(\App\Mediables::class, 'mediable');
    }

    public function prices()
    {
        return $this->hasMany(\App\ProductPrice::class, 'id', 'discount_id');
    }
}
