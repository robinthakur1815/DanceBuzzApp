<?php

namespace App\Model\Partner;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class CollectionOrder extends Model
{
    protected $connection = 'partner_mysql';
    protected $table = 'orders';

    protected $fillable = [
        'code', 'product_id', 'discount_id', 'meta',
        'purchaser_id', 'amount',  'currency', 'order_note',
        'payment_status', 'payment_id', 'payment_auth_token', 'payment_mode',
        'transaction_id', 'transaction_data', 'transaction_amount', 'transaction_date',
        'pg_request_data', 'platform_type', 'collection_id', 'created_by',
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

    public function purchaser()
    {
        return $this->belongsTo(\App\User::class, 'purchaser_id')->withTrashed();
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\User::class, 'created_by')->withTrashed();
    }

    public function collection()
    {
        return $this->belongsTo(\App\Model\PartnerCollection::class, 'collection_id');
    }
}
