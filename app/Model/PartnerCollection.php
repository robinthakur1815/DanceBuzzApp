<?php

namespace App\Model;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class PartnerCollection extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'collections';
    protected $connection = 'partner_mysql';

    protected $fillable = [
        'title', 'slug', 'published_content',  'saved_content', 'tags', 'status',
        'published_status', 'collection_type', 'vendor_id', 'services', 'published_at',
        'published_by', 'is_featured', 'is_recommended', 'published_price', 'categories',
        'vendor_class_id', 'created_by','updated_by', 'deleted_at', 'id'
    ];

    // protected $casts = [
    //     'services' => 'array',
    // ];
    // public $incrementing = false;

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

    // public function confirmOrders()
    // {
    //     return $this->hasMany(\App\Model\CollectionOrder::class, 'collection_id')->where('payment_status', PaymentStatus::Received);
    // }
    
}
