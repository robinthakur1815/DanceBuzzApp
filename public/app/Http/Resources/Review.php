<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Review extends JsonResource
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
            'review' => $this->review,
            'rating' => $this->rating,
            'approved_by' =>  isset($this->approvedBy) ? $this->approvedBy->name : $this->approvedBy,
            'purchaser_name' => $this->purchaser_name,
            'created_at' => $this->created_at,
            'approved_at' => $this->approved_at,
            'collection_name' => $this->collection ? $this->collection->title : '',
            'collection_type' => $this->collection ? $this->collection->collection_type : '',
            'status' => $this->review_status,
            'deleted_at' => $this->deleted_at,
        ];

        return $data;
    }
}
