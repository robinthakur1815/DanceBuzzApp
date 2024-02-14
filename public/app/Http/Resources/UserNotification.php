<?php

namespace App\Http\Resources;

use App\Enums\AppType;
use Illuminate\Http\Resources\Json\JsonResource;

class UserNotification extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);

        $data = [
            'title'         => $this->title,
            'description'   => $this->description,
            'created_at'    => (string) $this->created_at,
            'user_id'       => $this->user_id,
            'user_email'    => $this->user ? $this->user->email : '',
            'created_by'    => $this->createdBy ? $this->createdBy->name : '',
            'app_type'      => $this->app_type,
            'app_type_name' => '',
        ];

        if ($this->app_type) {
            $data['app_type_name'] = AppType::getKey($this->app_type);
        }

        return $data;
    }
}
