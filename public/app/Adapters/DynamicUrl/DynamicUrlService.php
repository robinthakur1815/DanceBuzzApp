<?php

namespace App\Adapters\DynamicUrl;

use App\Feed;
use App\Collection;
use App\Enums\FeedStatus;
use App\Enums\PublishStatus;
use App\Helpers\ImageHelper;
use App\Enums\CollectionType;
use Illuminate\Support\Facades\Storage;

class DynamicUrlService
{

    public function createDynamicUrlForFeed($feedId, $refresh = false)
    {
        $feed = Feed::with('feedable.medias','dynamicurls')->find($feedId);
        if (!$feed) {
            throw new \Exception("Could not create dynamic Url");
        }
        if ($feed->dynamicurls->count() > 0 && !$refresh) {
            return $feed->dynamicurls->first()->url;
        }
        $query = "type=feeds&post_id={$feedId}";
        $du = new DynamicUrl(config('app.client_url') . "/feeds", $query);

        $data['images'] = [];
        if ($feed->feedable->medias && count($feed->feedable->medias) > 0) {
           $data['images'] = $feed->feedable->medias->map(function ($media) {
                $media->full_url = Storage::url($media->url);
                return $media;
            });
        }

        $socialImageLink = $data['images'] && count($data['images']) > 0 ? $data['images'][0]['full_url'] . '/300-200.png' : null;


        if($socialImageLink && !self::remote_file_exists($socialImageLink)){

            $media = $data['images'] && count($data['images']) > 0 ? $data['images'][0]: null;
            if($media && $media->id){
               $status =  ImageHelper::createDynamicUrlNewImage($media->id);
               if($status){
                $socialImageLink = $data['images'][0]['full_url'] . '/300-200.png' ;
               }
            }
            
        }

        // info($socialImageLink);
        $socialMediaTag = [
            'socialTitle' => $feed->feedable->title,
            'socialDescription' => strip_tags($feed->feedable->description),
//            'socialImageLink' => $publishedContent->featured_image->url . "/" . $publishedContent->featured_image->name
            'socialImageLink' => $socialImageLink,
        ];
        $url = $du->setSocialMetaTagInfo($socialMediaTag)->build()->create();
        $this->createDynamicUrlModel($url, $du->getTargetUrl(), 'App\Feed', $feedId);
        return $url;
    }

    public function createDynamicUrlForAllFeed()
    {
        Feed::with('feedable.medias', 'createdBy', 'updatedBy')->where('status',FeedStatus::Active)
            ->chunk(200, function ($colls) {
                foreach ($colls as $coll) {
                    try {
                        $url = $this->createDynamicUrlForFeed($coll->id, false);
                        print "{$coll->id}: {$url}\n";
                        info($url);
                    } catch (\Exception$ex) {
                        $message = "{$coll->id}: " . $ex->getMessage();
                        print "$message\n";
                    }
                }
            });

    }

    public function createDynamicUrlForCampaign($collectionId, $refresh = false)
    {
        $collection = Collection::where('collection_type',CollectionType::campaigns)->with('dynamicurls')->find($collectionId);

        if (!$collection) {
            throw new \Exception("Could not create dynamic Url");
       }
        if ($collection->dynamicurls->count() > 0 && !$refresh) {
            return $collection->dynamicurls->first()->url;
        }

        $published_content = json_decode($collection->saved_content);

        $campaignData = $this->campaign($published_content);

        $type_id = $campaignData ? $campaignData['type_id'] : '';

        if ($type_id) {
            $query = "type=talentbox&id={$type_id}&camp_id={$collection->id}";
            $du = new DynamicUrl(config('app.client_url') . "/colorothon", $query);
            $socialImageLink = $campaignData['images'] && isset($campaignData['images']->full_url)? $campaignData['images']->full_url . '/300-200.png' : null;

            if($socialImageLink && !self::remote_file_exists($socialImageLink)){

                $media = $campaignData['images'] && isset($campaignData['images'])? $campaignData['images'] : null;
                if($media && isset($media->id)){
                   $status =  ImageHelper::createDynamicUrlNewImage($media->id);
                   if($status){
                    $socialImageLink = $campaignData['images']->full_url . '/300-200.png' ;
                   }
                }
                
            }
            $socialMediaTag = [
                'socialTitle' => $collection->title,
                'socialDescription' => strip_tags($campaignData['excerpt']),
                'socialImageLink' => $socialImageLink,
            ];

            $url = $du->setSocialMetaTagInfo($socialMediaTag)->build()->create();
            $this->createDynamicUrlModel($url, $du->getTargetUrl(), 'App\Collection', $collectionId);

         
            return $url;

        }

        

        return "" ;

    }

    public function createDynamicUrlForAllCampaign()
    {
        Collection::whereNotNull('published_content')->where('collection_type',CollectionType::campaigns)
            ->chunk(200, function ($colls) {
                foreach ($colls as $coll) {
                    try {
                        $url = $this->createDynamicUrlForCampaign($coll->id, false);
                        print "{$coll->id}: {$url}\n";
                    } catch (\Exception$ex) {
                        $message = "{$coll->id}: " . $ex->getMessage();
                        print "$message\n";
                    }
                }
            });

    }

    public function createDynamicUrlForYoungExperts($collectionId, $refresh = false)
    {
        $collection = Collection::where('collection_type',CollectionType::caseStudy)->whereNotNull('published_content')->with('dynamicurls')->find($collectionId);

        if (!$collection) {
            throw new \Exception("Could not create dynamic Url");
       }
        if ($collection->dynamicurls->count() > 0 && !$refresh) {
            return $collection->dynamicurls->first()->url;
        }

        $publishedContent = json_decode($collection->saved_content);

        $socialImageLink = isset($publishedContent->featured_image) && isset($publishedContent->featured_image->full_url) ? $publishedContent->featured_image->full_url . '/300-200.png' : '';

        if($socialImageLink && !self::remote_file_exists($socialImageLink)){

            $media_id = isset($publishedContent->featured_image) && isset($publishedContent->featured_image->id) ? $publishedContent->featured_image->id: '';
            if($media_id){
               $status =  ImageHelper::createDynamicUrlNewImage($media_id);
               if($status){
                $socialImageLink = $publishedContent->featured_image->full_url . '/300-200.png';
               }
            }
            
        }

        $featuredImage = isset($publishedContent->featured_image) && isset($publishedContent->featured_image->full_url) ? $publishedContent->featured_image->full_url . '/default.png' : '';

            $query = "type=casestudies&id={$collection->id}&title={$collection->title}&slug={$collection->slug}&featuredImage={$featuredImage}&isImage=true";
            $du = new DynamicUrl(config('app.client_url') . "/casestudies", $query);
            $socialMediaTag = [
                'socialTitle' => $collection->title,
                'socialDescription' => isset($publishedContent->excerpt) ? strip_tags($publishedContent->excerpt) : "",
                'socialImageLink' => $socialImageLink,
            ];

            $url = $du->setSocialMetaTagInfo($socialMediaTag)->build()->create();
            $this->createDynamicUrlModel($url, $du->getTargetUrl(), 'App\Collection', $collectionId);

         
            return $url;


    }



    public function createDynamicUrlForAllYoungExperts()
    {
        Collection::whereNotNull('published_content')->where('collection_type',CollectionType::caseStudy)
            ->chunk(200, function ($colls) {
                foreach ($colls as $coll) {
                    try {
                        $url = $this->createDynamicUrlForYoungExperts($coll->id, false);
                        print "{$coll->id}: {$url}\n";
                    } catch (\Exception$ex) {
                        $message = "{$coll->id}: " . $ex->getMessage();
                        print "$message\n";
                    }
                }
            });

    }



    /**
     * Script for Generating Dynamic Urls 
     */
    public static function generateDynamicUrlForAllYoungExperts()
    {
        Collection::whereIn('status',[PublishStatus::Published,PublishStatus::Draft])->where('collection_type',CollectionType::caseStudy)
            ->chunk(200, function ($colls) {
                foreach ($colls as $coll) {
                    try {
                        $help = new DynamicUrlService();
                        $url = $help->createDynamicUrlForYoungExperts($coll->id, true);
                        print "{$coll->id}: {$url}\n";
                    } catch (\Exception$ex) {
                        $message = "{$coll->id}: " . $ex->getMessage();
                        print "$message\n";
                    }
                }
            });

    }


    public static function generateDynamicUrlForAllCampaign()
    {
        Collection::whereIn('status',[PublishStatus::Published,PublishStatus::Draft])->where('collection_type',CollectionType::campaigns)
            ->chunk(200, function ($colls) {
                foreach ($colls as $coll) {
                    try {
                        $help = new DynamicUrlService();
                        $url = $help->createDynamicUrlForCampaign($coll->id, true);
                        print "{$coll->id}: {$url}\n";
                    } catch (\Exception$ex) {
                        $message = "{$coll->id}: " . $ex->getMessage();
                        print "$message\n";
                    }
                }
            });

    }


    public static function generateDynamicUrlForAllFeed()
    {
        Feed::with('feedable.medias', 'createdBy', 'updatedBy')->where('status',FeedStatus::Active)
            ->chunk(200, function ($colls) {
                foreach ($colls as $coll) {
                    try {
                        $help = new DynamicUrlService();
                        $url = $help->createDynamicUrlForFeed($coll->id,true);
                        print "{$coll->id}: {$url}\n";
                        info($url);
                    } catch (\Exception$ex) {
                        $message = "{$coll->id}: " . $ex->getMessage();
                        print "$message\n";
                    }
                }
            });

    }

    

    private function campaign($published_content)
    {
        $type = '';
        $type_id = '';
        $excerpt = '';
        $images = '';

        if (isset($published_content->campaign_type) and $published_content->campaign_type) {
            $type = $published_content->campaign_type->name;
            $type_id = $published_content->campaign_type->id;
        }

        if (isset($published_content->excerpt) and $published_content->excerpt) {
            $excerpt = $published_content->excerpt;
        }

        if (isset($published_content->images) and $published_content->images and count($published_content->images)) {
            $images = $published_content->images[0] ;
        }

        return [
            'type' => $type,
            'type_id' => $type_id,
            'images' => $images,
            'excerpt' => $excerpt,
        ];
    }

    private function createDynamicUrlModel($url, $targetUrl, $type, $id)
    {
        $duModel = new \App\DynamicUrl();
        $duModel->dynamicurlable_type = $type;
        $duModel->dynamicurlable_id = $id;
        $duModel->url = $url;
        $duModel->target_url = $targetUrl;
        $duModel->save();
        return $duModel;
    }

    public static function remote_file_exists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode == 200) {
            return true;
        }
    }

}
