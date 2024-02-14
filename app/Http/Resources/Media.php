<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Media extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name'        => $this->name,
            'reference'   => $this->reference,
            'path'        => $this->path,
            'mime_type'   => $this->mime_type,
            'size'        => $this->size,
            'media_id'    => $this->media_id,
        ];
    }
}
