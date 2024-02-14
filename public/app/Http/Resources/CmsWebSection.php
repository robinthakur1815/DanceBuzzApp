<?php

namespace App\Http\Resources;

use App\Enums\CollectionType;
use App\Http\Resources\WebData;
use App\Http\Resources\WebDataCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CmsWebSection extends JsonResource
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
            'title' => $this->title,
            'slug' => $this->slug,
            'name' => $this->name,
            'alignment_type' => $this->alignment_type,
            'sequence' => $this->sequence,
        ];

        if (! isset($this->basicList) || ! $this->basicList) {
            // $data['created_by'] = $this->createdBy ? $this->createdBy->name : null;
            $data['collection_count'] = $this->collections_count;
            $data['cta'] = ($this->cta) ? json_decode($this->cta) : null;
            $data['deleted_at'] = $this->deleted_at;
            $data['heading'] = $this->heading;
            $data['sub_heading'] = $this->sub_heading;
            $data['created_at'] = $this->created_at;
        }

        return $data;
    }
}
