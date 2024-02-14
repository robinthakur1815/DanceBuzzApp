<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StudentStoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
   {
  
    $sub_category = 'N/A';
    $phone = 'N/A';
    $name = "";
    $email = "";
    $subCategoryName= "";
    $campaign_type ="";
    $status = [];
    

    if ($this->meta) {
        $metaData = json_decode($this->meta,true);
        if (isset($metaData['sub_category']['saved_content']['current_user']['name']) and $metaData['sub_category']['saved_content']['current_user']['name']) {
         $subCategoryName = (string)$metaData['sub_category']['saved_content']['current_user']['name'];
        }
        $metaData = json_decode($this->meta,true);
        if (isset($metaData['category']['title']) and $metaData['category']['title']) {
            $categoryName = (string)$metaData['category']['title'];
           }
           $metaData = json_decode($this->meta,true);
           if (isset($metaData['campaign']['name']) and $metaData['campaign']['name']) {
               $campaignName = (string)$metaData['campaign']['name'];
              }
              $metaData = json_decode($this->meta,true);
              if (isset($metaData['campaign']['status']) and $metaData['campaign']['status']) {
                  $status = (string)$metaData['campaign']['status'];
                 }
                 $metaData = json_decode($this->meta,true);
                 if (isset($metaData['campaign']['created_at']) and $metaData['campaign']['created_at']) {
                     $created_at = (string)$metaData['campaign']['created_at'];
                    }
                    $metaData = json_decode($this->meta,true);
                    if (isset($metaData['campaign']['email']) and $metaData['campaign']['email']) {
                        $email = (string)$metaData['campaign']['email'];
                       }
                       $metaData = json_decode($this->meta,true);
                       if (isset($metaData['campaign']['phone']) and $metaData['campaign']['phone']) {
                           $phone = (string)$metaData['campaign']['phone'];
                          }
             
                
                }
    $data = [
                'id'                     => $this->id,
                'campaign_name'          => $email,
                'category_name'          => $phone,
                'sub_category_name'      => $subCategoryName,
                'name'                   => $this->name,
                'category_id'            => $this->category_id,
                'sub_category_id'        => $this->sub_category_id,
                'status'                 => $status,
               // 'created_at'             =>$created_at,
                //'campaign_type'          => $campaign_type,
             'meta'                   => $this->meta,
                
       ];

    return $data;
}
}
