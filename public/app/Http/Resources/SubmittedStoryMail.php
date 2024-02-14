<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class SubmittedStoryMail extends JsonResource
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
        $this->load('campaign', 'category', 'subCategory');

        $data = [
            'created_at' => (string) Carbon::parse($this->created_at)->format('M d, Y  H:iA'),
            'campaign_type' => null,
            'campaign_name' => $this->campaign ? $this->campaign->title : null,
            'category_name' => $this->category ? $this->category->title : null,
            'sub_category_name' => $this->subCategory ? $this->subCategory->title : null,
            'description' => $this->description ? $this->description : null,
            'comment' => $this->comments ? $this->comments : null,
            'attachment_type' => null,
        ];

        if ($this->campaign) {
            $campaignContent = json_decode($this->campaign->published_content);

            if ($campaignContent && $campaignContent->campaign_type && $campaignContent->campaign_type->name) {
                $data['campaign_type'] = $campaignContent->campaign_type->name;
            }
        }

        if ($this->category) {
            $categoryContent = json_decode($this->category->published_content);

            if ($categoryContent && $categoryContent->category_type && count($categoryContent->category_type) > 0) {
                $list = array_column($categoryContent->category_type, 'name');
                $data['attachment_type'] = implode(', ', $list);
            }
        }

        return $data;
    }
}
