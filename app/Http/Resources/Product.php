<?php

namespace App\Http\Resources;

use App\Category;
use App\Collection;
use App\Tag;
use Illuminate\Http\Resources\Json\JsonResource;

class Product extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $datas = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ? $this->description : '',
            'sku' => $this->sku ? $this->sku : '',
            'stock' => $this->stock ? $this->stock : 0,
            'merchant_code' => $this->merchant_code ? $this->merchant_code : '',
            'status' => $this->status ? $this->status : '',
        ];

        if ($this->tags) {
            $tag = json_decode($this->tags);
            $datas['tags'] = Tag::whereIn('id', $tag)->get();
        }

        if ($this->categories) {
            $category = json_decode($this->categories);
            $datas['categories'] = Category::whereIn('id', $category)->get();
        }
        $datas['collections'] = $this->collection_id ? Collection::where('id', $this->collection_id)->get() : [];

        return $datas;
    }
}
