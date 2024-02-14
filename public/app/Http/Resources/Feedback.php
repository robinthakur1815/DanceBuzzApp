<?php

namespace App\Http\Resources;

use App\Enums\AppType;
use App\Enums\FeedbackType;
use App\Enums\PlatformType;
use App\User;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

// use App\Enums\Rating;

class Feedback extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $meta = $this->meta ? json_decode($this->meta) : '';
        $medias = null;
        $user = null;
        $category = "";

        if ($this->category) {
            $category = $this->category->name;
        }
        if ($this->medias) {
            $medias = $this->getMedias($this->medias);
        }

        if ($this->user_id) {
            $user = User::find($this->user_id);
            $user = $user->name;
        }

        $data = [
            'feedback_type'     => $this->type ? FeedbackType::getKey($this->type) : '',
            'platform_type'     => $this->platform_type ? PlatformType::getKey($this->platform_type) : '',
            'app_type'          => $this->app_type ? AppType::getKey($this->app_type) : '',
            'description'       => $this->description,
            'created_at'        => (string) $this->created_at,
            'userName'          => $user,
            'medias'            => $medias,
            'meta'              => $this->getMetaData(json_decode($this->meta)),
            'meta_check'        => json_decode($this->meta),
            'rating'            => $this->rating ? $this->rating : '',
            'category'          => $category ,
            'id'                => $this->id
            // String modelNo = '';
            // String brand = '';
            // String manufacturer = '';
            // String androidversion = '';
        ];

        return $data;
    }

    private function getMedias($medias)
    {
        $updatedMedias = collect();

        foreach ($medias as $key => $media) {
            $updatedMedias->push(
                [
                    'name' => $media->name,
                    'url' =>  Storage::disk('s3')->url($media->url),
                ]
            );
        }

        return $updatedMedias;
    }

    private function getMetaData($meta)
    {
        $metaData = $meta->meta ? $meta->meta : null;
        $data = null;

        if ($meta) {
            $data = [
                'modelNo' => isset($metaData->modelNo) ? $metaData->modelNo : '',
                'brand' => isset($metaData->brand) ? $metaData->brand : '',
                'manufacturer' => isset($metaData->manufacturer) ? $metaData->manufacturer : '',
                'androidversion' => isset($metaData->version) ? $metaData->version : '',
            ];
        }

        return $data;
    }
}
