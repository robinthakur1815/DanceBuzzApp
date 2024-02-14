<?php

namespace App\Http\Resources;

use App\Http\Resources\CmsWebSectionCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class CmsWebPage extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if ($this->sections) {
            $this->sections->map(function ($item) {
                $item->basicList = true;
            });
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'content' => $this->content,
            'section_count' => count($this->sections),
            'sections' => $this->sections,
            'created_by' => $this->createdBy && $this->createdBy->name ? $this->createdBy->name : '',
            'created_at' => $this->created_at,
            'deleted_at' => $this->deleted_at,
            'sections' => new CmsWebSectionCollection($this->sections),
            'meta' => $this->meta ? $this->meta : null,
        ];
    }
}
