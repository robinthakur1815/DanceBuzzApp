<?php

namespace App\Http\Resources;

use App\Enums\ClassPublishStatus;
use App\Enums\CollectionType;
use App\Enums\PublishStatus;
use App\Enums\Bytype;
use App\Model\Partner\PartnerLiveClass;
use App\Model\Partner\Service;
use App\Vendor;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class Blog extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $saved_content = json_decode($this->saved_content);
        $is_live_class = false;
        if ($this->collection_type == CollectionType::faqs && $this->onlyForMobile) {
            $data = [
                'id' => $this->id,
                'title' => $saved_content->title,
                'content'  => isset($saved_content->content) ? $saved_content->content : '',
            ];

            return $data;
        }

        if ($this->collection_type == CollectionType::classDeck) {
            $is_live_class = true;
        }

        $status = $this->status;

        if ($this->vendor_class_id) {
            $status = $this->published_content ? ClassPublishStatus::Published : ClassPublishStatus::Draft;
        }

        $product_price_id = '';
        $product_id = '';
        $price = '';
        $session_price = "";
        $is_free = false;

        if (in_array($this->collection_type, [CollectionType::classes, CollectionType::classDeck])) {
            if (isset($saved_content->is_free)) {
                $is_free = $saved_content->is_free;
            }
        }

        if ($this->product) {
            foreach ($this->product->packages as $package) {
                if ($package->pivot->is_active) {
                    $price = (string) $package->price;
                    $is_free = false;
                }
                $product_price_id = (string) $package->id;
            }
            $product_id = (string) $this->product->id;
        }

        if ($saved_content and isset($saved_content->session_price)) {
            $session_price = $saved_content->session_price;
        }


        if (! isset($this->details)) {

            $data = [
                'id'               => $this->id,
                'title'            => $saved_content->title,
                'slug'             => $this->slug,
                'collection_type'  => $this->collection_type,
                'is_featured'      => $this->is_featured ? $this->is_featured : false,
                'is_recommended'   => $this->is_recommended,
                'status'           => $status,
                'created_by'       => $this->createdBy ? $this->createdBy->name : '--',
                'created_at'       => $this->created_at,
                'published_by'     => $this->updatedBy ? $this->updatedBy->name : '--',
                'published_at'     => $this->published_at ? $this->published_at : $this->updated_at,
                'deleted_at'       => $this->deleted_at ? $this->deleted_at : null,
                'medias'           => $this->medias,
                'name'             => $this->title,
                'vendor'           => isset($this->vendor) && $this->vendor && $this->vendor->name ? $this->vendor->name : '',
                'vendor_class_id'  => $this->vendor_class_id,
                'is_live_class'    => $is_live_class,
                'vendor_id'        => $this->vendor_id,
                'is_free'          => $is_free,
                'product_id'       => $product_id,
                'product_price_id' => $product_price_id,
                'price'            => $price,
                'session_price'    => $session_price,
                'stories_count'    => $this->stories_count,
                'By_type'          => Bytype::getKey($this->is_private)

             ];

            $data['dynamicUrl']  = $this->getDynamicUrl() ? $this->getDynamicUrl() : '';
              
            if($this->collection_type == CollectionType::campaigns)
            {
                $data['is_private' ] =  $this->is_private;
              
            } 
            // $saved_content = json_decode($this->saved_content);
            //$data['featured_image'] = (isset($saved_content->featured_image) && $saved_content->featured_image) ? Storage::disk('s3')->url($saved_content->featured_image->url) : null;
            $data['featured_image'] = (isset($saved_content->featured_image) && $saved_content->featured_image) && isset($saved_content->featured_image->url) ? Storage::disk('s3')->url($saved_content->featured_image->url) : null;
            $data['excerpt'] = (isset($saved_content->excerpt) && $saved_content->excerpt) ? $saved_content->excerpt : null;
            if ($this->collection_type == CollectionType::videos) {
                $data['featured_video'] = isset($saved_content->featured_video) ? $saved_content->featured_video : null;
            } elseif ($this->collection_type == CollectionType::testimonials) {
                // $saved_content = json_decode($this->saved_content);
                $data['author_name'] = isset($saved_content->author_name) ? $saved_content->author_name : '';
                $data['author_company'] = isset($saved_content->author_company) ? $saved_content->author_company : '';
                $data['author_designation'] = isset($saved_content->author_designation) ? $saved_content->author_designation : '';
            } elseif ($this->collection_type == CollectionType::sponsers) {
                $datas['name'] = $this->title;
            } elseif ($this->collection_type == CollectionType::galleries) {
                $saved_content = json_decode($this->saved_content);
                $data['images'] = isset($saved_content->images) ? $saved_content->images : [];
            } elseif ($this->collection_type == CollectionType::clients) {
                $saved_content = json_decode($this->saved_content);
            // $data['featured_image'] = (isset($saved_content->featured_image) && $saved_content->featured_image) ? Storage::disk('s3')->url($saved_content->featured_image->url) : null;
            } elseif ($this->collection_type == CollectionType::people) {
                $saved_content = json_decode($this->saved_content);
            // $data['featured_image'] = (isset($saved_content->featured_image) && $saved_content->featured_image) ? Storage::disk('s3')->url($saved_content->featured_image->url) : null;
            } elseif ($this->collection_type == CollectionType::careers) {
                $saved_content = json_decode($this->saved_content);
                // $data['author_designation'] = (isset($saved_content->author_designation) && $saved_content->author_designation) ? $saved_content->author_designation : null;
                $data['location'] = (isset($saved_content->location) && $saved_content->location) ? $saved_content->location : null;
                $data['highlights'] = (isset($saved_content->highlights) && $saved_content->highlights) ? $saved_content->highlights : null;
                $data['fee'] = (isset($saved_content->fee) && $saved_content->fee) ? $saved_content->fee : null;
                $data['location'] = (isset($saved_content->location) && $saved_content->location) ? $saved_content->location : null;
            } elseif ($this->collection_type == CollectionType::awards) {
                $saved_content = json_decode($this->saved_content);
                $data['date'] = isset($saved_content->date) ? $saved_content->date : null;
                $data['author_name'] = isset($saved_content->author_name) ? $saved_content->author_name : null;
            } elseif ($this->collection_type == CollectionType::services) {
                $saved_content = json_decode($this->saved_content);
            // $data['featured_image'] = (isset($saved_content->featured_image) && $saved_content->featured_image) ? Storage::disk('s3')->url($saved_content->featured_image->url) : null;
            } elseif ($this->collection_type == CollectionType::campaignCategories) {
                $saved_content = json_decode($this->saved_content);
                $data['sub_categories'] =
                    (isset($saved_content->sub_categories) && $saved_content->sub_categories) ? $saved_content->sub_categories : null;
            } elseif ($this->collection_type == CollectionType::carnivalActivitites) {
                $saved_content = json_decode($this->saved_content);
                $data['author_name'] = isset($saved_content->author_name) ? $saved_content->author_name : '';
            } elseif ($this->collection_type == CollectionType::campaigns) {
                $saved_content = json_decode($this->saved_content);
                $data['type'] =
                    (isset($saved_content->campaign_type) && $saved_content->campaign_type) ? $saved_content->campaign_type->name : null;
            }

            // $saved_content->service = null;
            // if (isset($saved_content->service_id) and $saved_content->service_id) {
            //     $service = Service::where('id', $saved_content->service_id)->first();
            //     $saved_content->service = $service;
            //     $data['saved_content'] = $saved_content;
            // }

            return $data;
        }
        if (isset($this->details) && $this->details) {
            $savedContent = $this->saved_content ? json_decode($this->saved_content, true) : null;
            $vendor = isset($savedContent['vendor']) && $savedContent['vendor'] && $savedContent['vendor']['id'] ? $this->getVendor($savedContent['vendor']['id']) : null;
            $savedContent['vendor'] = $vendor;

            // $saved_content['vendor_services'] = null;
            $service = null;

            if (isset($saved_content->service_id) and $saved_content->service_id) {
                $service = Service::where('id', $saved_content->service_id)->first();
                // $saved_content['vendor_services'] = $service;
            }

            $liveClass = '';
            if ($this->collection_type == CollectionType::classDeck) {
                $liveClass = PartnerLiveClass::where('vendor_class_id', $this->vendor_class_id)->first();

                if ($liveClass) {
                    $remainder = $liveClass->duration % 60;
                    $savedContent['duration'] = $liveClass->duration;
                }
            }

            // $vendor = isset($savedContent->vendor) && $savedContent->vendor && $savedContent->vendor->id ? $this->getVendor($savedContent->vendor->id) : null ;

            return [
                'id'               => $this->id,
                'title'            => $this->title,
                'slug'             => $this->slug,
                'collection_type'  => $this->collection_type,
                'is_featured'      => $this->is_featured,
                'saved_content'    => $savedContent,
                'statusName'       => PublishStatus::getKey($this->status),
                'status'           => $status,
                'seos'             => isset($savedContent['seo']) ? $savedContent['seo'] : null,
                'created_by'       => $this->createdBy ? $this->createdBy->name : '--',
                'created_at'       => $this->created_at,
                'published_by'     => $this->updatedBy ? $this->updatedBy->name : '--',
                'published_at'     => $this->published_at ? $this->published_at : null,
                'deleted_at'       => $this->deleted_at ? $this->deleted_at : null,
                'featured_image'   => isset($savedContent->featured_image) ? Storage::disk('s3')->url($saved_content->featured_image->url) : null,
                'vendor_id'        => $this->vendor_id,
                'vendor_class_id'  => $this->vendor_class_id,
                'vendor'           => $vendor,
                'location_id'      => isset($savedContent['location_id']) ? $savedContent['location_id'] : '',
                'vendor_services'  => $service,
                'live_class'       => $this->collection_type == CollectionType::classDeck ? $liveClass : '',
                'statusPublished'  => isset($this->statusPublished) ? $this->statusPublished : null,
                'is_live_class'    => $is_live_class,
                'is_free'          => $is_free,
                'product_id'       => $product_id,
                'product_price_id' => $product_price_id,
                'price'            => $price,
                'session_price'    => $session_price,
                'stories_count'    => $this->stories_count,
                'By_type'          => Bytype::getKey($this->is_private),
                'dynamicUrl'  => $this->getDynamicUrl() ? $this->getDynamicUrl() : '',
                // 'duration_min' =>
            ];
        }
    }

    public function getVendor($id)
    {
        $vendor = Vendor::select('id', 'name')->find($id);

        if ($vendor) {
            return $vendor;
        }

        return null;
    }

    private function getDynamicUrl()
    {
        if (!$this->relationLoaded('dynamicurls')) {
            $this->load('dynamicurls');
        }

        if ($this->dynamicurls->count() > 0) {
            return $this->dynamicurls->last()->url;
        }
        return null;
    }
}
