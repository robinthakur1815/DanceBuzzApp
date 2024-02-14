<?php

namespace App;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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

    public function product()
    {
        return $this->belongsTo(\App\Product::class, 'product_id', 'id')->withTrashed();
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
        return $this->belongsTo(\App\Collection::class, 'collection_id')->withTrashed();
    }

    public function withoutTrashCollection()
    {
        return $this->belongsTo(\App\Collection::class, 'collection_id');
    }

    public function couponApplied()
    {
        return $this->belongsTo(\App\Coupon::class, 'discount_id', 'id')->withTrashed();
    }

    public static function filtered($request, $userIds = null)
    {
        $orders = (new self)->newQuery()->with('product.collection', 'product.prices', 'couponApplied');

        if ($userIds) {
            $orders = $orders->whereIn('purchaser_id', $userIds);
        }

        if (isset($request->start_date) && $request->start_date) {
            $orders = $orders->whereDate('created_at', '>=', $request->start_date);
        }

        if (isset($request->end_date) && $request->end_date) {
            $orders = $orders->whereDate('created_at', '<=', $request->end_date);
        }

        if (isset($request->min_amount) && $request->min_amount) {
            $orders = $orders->where('amount', '>=', $request->min_amount);
        }

        if (isset($request->max_amount) && $request->max_amount) {
            $orders = $orders->where('amount', '<=', $request->max_amount);
        }

        if (isset($request->status) && $request->status) {
            $orders = $orders->where('payment_status', $request->status);
        }

        if (isset($request->product_id) && $request->product_id) {
            $orders = $orders->where('product_id', $request->product_id);
        }

        if (isset($request->transaction_id) && $request->transaction_id) {
            $orders = $orders->where('transaction_id', $request->transaction_id);
        }

        if (isset($request->max_rows) && $request->max_rows) {
            $orders = $orders->latest()->paginate($request->max_rows);
        } else {
            $orders = $orders->latest()->get();
        }

        return $orders;
    }
}
