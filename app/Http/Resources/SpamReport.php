<?php

namespace App\Http\Resources;

use App\Enums\SpamReportStatus;
use Illuminate\Http\Resources\Json\JsonResource;

class SpamReport extends JsonResource
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
            'id' => $this->id,
            'reported_at' => (string) $this->created_at,
            'deleted_at' => (string) $this->deleted_at,
            'reported_by_id' => $this->createdBy ? $this->createdBy->id : '',
            'reported_by_name' => $this->createdBy ? $this->createdBy->name : '',
            'updated_by_id' => $this->updatedBy ? $this->updatedBy->id : '',
            'updated_by_name' => $this->updatedBy ? $this->updatedBy->name : '',
            'status' => $this->status,
            'is_pending' => $this->status == SpamReportStatus::Pending ? true : false,
            'comment_description' => '',
        ];

        $comment = $this->comment;

        $data['comment_description'] = $comment ? $comment->comment : '';
        $data['feed_comment'] = $comment && $comment->commentable_type == \App\Feed::class ? true : false;

        return $data;
    }
}
