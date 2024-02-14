<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Like extends JsonResource
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
        $name = null;
        $profile_url = null;

        if ($this->users) {
            $name = $this->users->name;
            $profile_url = $this->users->partnerAvatar ? $this->users->partnerAvatar : null;
        }

        return [
            'id' => $this->id,
            'name' => $name,
            'profile_url' => $profile_url,
            'created_at' => (string) $this->updated_at,   // Using updated at key for users liking and disliking again and again
        ];
    }
}
