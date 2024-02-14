<?php

namespace App\Http\Resources;

use function GuzzleHttp\json_decode;
use Illuminate\Http\Resources\Json\JsonResource;

class Seo extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $meta = $this->meta ? json_decode($this->meta) : null;

        return [
            'id' =>  $this->id,
            'meta_title' =>  $meta ? $meta->meta_title : null,
            'meta_keywords' =>  $meta ? $meta->meta_keywords : null,
            'meta_description' =>  $meta ? $meta->meta_description : null,
            'og_title' =>  $meta ? $meta->og_title : null,
            'og_description' =>  $meta ? $meta->og_description : null,
            'og_type' =>  $meta ? $meta->og_type : null,
            'og_url' =>  $meta ? $meta->og_url : null,
            'og_image_alt' =>  $meta ? $meta->og_image_alt : null,
            'og_site_name' =>  $meta ? $meta->og_site_name : null,
            'twitter_card' =>  $meta ? $meta->twitter_card : null,
            'twitter_site' =>  $meta ? $meta->twitter_site : null,
            'twitter_title' =>  $meta ? $meta->twitter_title : null,
            'twitter_description' =>  $meta ? $meta->twitter_description : null,
            'twitter_image_alt' =>  $meta ? $meta->twitter_image_alt : null,
        ];
    }
}
