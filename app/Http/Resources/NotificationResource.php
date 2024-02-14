<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {       
        $user = auth()->user();
        $notifiable_id   = $this->collection_id;
        $type            = $this->collection_type;
        $collection_name = $this->collection_name;
        $sent_at         = $this->sent_at;
        $title           = $this->title;
        $description     = $this->description;

        return [
            'notifiable_id'         => $notifiable_id,
            'type'                  => $type,
            'collection_name'       => $collection_name,
            'created_by'            => $user,
            'sent_at'               => $sent_at,
            'title'                 => $title,
            'description'           => $description,
        ];

        return parent::toArray($request);
    }
}
