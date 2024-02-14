<?php

namespace App\Http\Resources;

use App\Collection;
use App\Enums\CollectionType;
use App\Enums\LiveClassStatus;
use App\Model\Partner\Service;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class LiveClassSession extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $className = '';
        if ($this->vendorLiveClass and $this->vendorLiveClass->vendorClass) {
            $className = $this->vendorLiveClass->vendorClass->name;
        }
        $duration = $this->convertToHoursMins((int) $this->vendorLiveClass->duration, '%02d h %02d m');

        if ($this->is_recording) {
            return [
                'id'                    => $this->id,
                'recordingname'         => 'Recording'.$this->start_date_time->format('d-m-Y'),
                'date'                  => $this->start_date_time->format('d M Y'),
                'nextdate_day'          => $this->start_date_time->format('d'),
                'nextdate_month_year'   => $this->start_date_time->format('M Y'),
                'time'                  => $duration,
                'duration'              => $duration,
                'is_download'           => false,
                'url'                   => $this->url,
                'title'                 => $this->start_date_time->format('d'),
                'class_name'            => $className,
                'thumbnail'             => count($this->images) ? $this->images[0] : '',
                'is_live'               => false,
                'collection_id'         => $this->collection_id ?? '0',
            ];
        }

        $endTime = $this->start_date_time->addMinutes((int) $this->vendorLiveClass->duration);

        $status = $this->status;
        if ($endTime->lt(now()) and in_array($this->status, [LiveClassStatus::Active, LiveClassStatus::Expired, LiveClassStatus::ReActivate, LiveClassStatus::ReSchedule])) {
            $status = LiveClassStatus::Expired;
        }

        if ($status == LiveClassStatus::ReActivate) {
            $status = LiveClassStatus::Active;
        }

        $data = [
            'id'                    => $this->id,
            'duration'              => $duration,
            'transaction_id'        => 'UTIB20019',
            'desktop_Link'          => '',
            'nextdate'              => $this->start_date_time->format('d M Y'),

            'day'                   => $this->start_date_time->format('d'),
            'month_year'            => $this->start_date_time->format('M Y'),
            'start_time'            => $this->start_date_time->format('h:i A'),

            'nextdate_day'          => $this->start_date_time->format('d'),
            'nextdate_month_year'   => $this->start_date_time->format('M Y'),
            'time'                  => $this->start_date_time->format('h:i A'),
            'status'                => LiveClassStatus::getKey($status),
            'title'                 => $this->start_date_time->format('d'),
            'class_name'            => $className,
            'thumbnail'             => '',
            'collection_id'         => $this->collection_id ?? '0',
            'is_live'               => $this->status == LiveClassStatus::Running ? true : false,
        ];

        if ($this->is_details) {
            $vendorClass = $this->vendorLiveClass->vendorClass;
            $service = '';
            $serviceData = Service::where('id', $vendorClass->service_id)->first();
            if ($serviceData) {
                $service = $serviceData->name;
            }

            $service_icon = '';
            $imageUrl = public_path('/').'images/servicesMobile/service'.$vendorClass->service_id.'.svg';
            if (is_file($imageUrl)) {
                $service_icon = url('/').'/images/servicesMobile/service'.$vendorClass->service_id.'.svg';
            } else {
                $service_icon = url('/').'/images/servicesMobile/service1.svg';
            }

            $collection = Collection::where('vendor_class_id', $vendorClass->id)->first();
            $slug = '';
            if ($collection) {
                $slug = $collection->slug;
            }
            $data['live_class_id'] = $this->vendorLiveClass->id;
            $data['class_id'] = $vendorClass->id;
            $data['service'] = $service;
            $data['service_icon'] = $service_icon;
            $data['collection_type'] = CollectionType::classDeck;
            $data['slug'] = $slug;
            $data['featured_image'] = $collection ? $this->featured_image($collection) : null;
        }

        return $data;

        return parent::toArray($request);
    }

    private function convertToHoursMins($time, $format = '%02d:%02d')
    {
        if ($time <= 60) {
            return $time.'m';
        }

        $hours = floor($time / 60);
        $minutes = ($time % 60);

        return sprintf($format, $hours, $minutes);
    }

    public function featured_image($collection)
    {
        $published_content = $collection->published_content ? json_decode($collection->published_content) : null;
        if (! $published_content) {
            return null;
        }
        $featured_image = null;
        $collection->load('medias');
        if (count($collection->medias)) {
            $mediaData = $collection->medias[0];
            if ($mediaData) {
                $featured_image = $mediaData;
                if ($featured_image && $featured_image['url']) {
                    $featured_image = Storage::disk('s3')->url($featured_image['url']);
                }
            }
        } else {
            if (isset($published_content->featured_image) && $published_content->featured_image) {
                $featured_image = $published_content->featured_image;
                if ($published_content->featured_image) {
                    $featured_image = $published_content->featured_image;
                    if ($published_content->featured_image && isset($published_content->featured_image->url)) {
                        $featured_image = Storage::disk('s3')->url($featured_image->url);
                    } else {
                        if (isset($featured_image) and $featured_image and $featured_image->url) {
                            $featured_image = Storage::disk('s3')->url($featured_image->url);
                        }
                    }
                }
            }
        }

        return $featured_image;
    }
}
