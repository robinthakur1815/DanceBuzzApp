<?php

namespace App\Http\Resources;

use App\Category;
use App\Enums\PaymentStatus;
use App\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

class WebOrder extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'amount' => $this->amount,
            'payment_mode' => $this->payment_mode,
            'currency'  => $this->currency,
            'order_note' => $this->order_note,
            'status' => $this->payment_status,
            'payment_status' => PaymentStatus::getKey($this->payment_status),
            'payment_date' => $this->created_at,
            'transaction_date' => $this->transaction_date,
            'attendees' => '',
        ];

        if ($this->meta) {
            $meta = json_decode($this->meta);
            if (isset($meta->attendees) && $meta->attendees) {
                $data['attendees'] = $meta->attendees;
            }
        }
        $data['product_id'] = $this->product ? $this->product->id : '';
        $data['collection_id'] = ($this->product && $this->product->collection) ? $this->product->collection->id : '';
        $data['collection_type'] = ($this->product && $this->product->collection) ? $this->product->collection->collection_type : '';
        $data['collection_title'] = ($this->product && $this->product->collection) ? $this->product->collection->title : '';
        $data['collection_slug'] = ($this->product && $this->product->collection) ? $this->product->collection->slug : '';
        $data['product_price'] = ($this->product && $this->product->prices && count($this->product->prices) > 0) ? $this->product->prices[0]['price'] : '';

        return $data;
        // return parent::toArray($request);
    }
}
