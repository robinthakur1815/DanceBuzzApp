<?php

namespace App\Http\Resources;

use App\Enums\CollectionType;
use App\Http\Resources\WebData;
use App\Http\Resources\WebDataCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class WebSection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $allContent = json_decode($this->content);

        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'name' => $this->name,
            // 'pages' =>  isset($this->pages)  ? $this->pages : null,
            // 'created_by' => $this->created_by ? $this->createdBy->name : null,
            'created_at' => $this->created_at,
            'collection_count' => $this->collections_count,
            'alignment_type' => $this->alignment_type,
            'sequence' => $this->sequence,
            'deleted_at' => $this->deleted_at,
            'collections' => ($this->collections && count($this->collections) > 0) ? new WebDataCollection($this->collections) : '',
            'cta' => ($this->cta) ? json_decode($this->cta) : null,
            'content' => isset($allContent->content) ? $allContent->content : null,
            // 'highlight' => $highlight,
            'heading' => $this->heading,
            'sub_heading' => $this->sub_heading,
            'featured_image' => null,
        ];

        if (isset($allContent->image) && $allContent->image) {
            $image = $allContent->image;
            if (!str_contains($image->url, 'http')) {
                $image->url = Storage::url($image->url);
            }
            
            $data['featured_image'] = $image;
        }

        return $data;
    }
}
