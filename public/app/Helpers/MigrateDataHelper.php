<?php

namespace App\Helpers;

use App\Enums\CollectionType;
use App\Collection;
use App\CustomFeed;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
final class MigrateDataHelper
{
    public static function migrateCollectionImage()
    {
        $types = [CollectionType::caseStudy];
        $collections = Collection::whereIn('collection_type', $types)->get();
        foreach($collections  as $collection){
            $jsonData = json_decode($collection->saved_content);
            $urlData = ImageHelper::addImageUrl($jsonData);
            if(!$urlData->isImage and $urlData->featured_image){
                $data = explode('https://dbcms.s3.ap-south-1.amazonaws.com/', $urlData->featured_image);
                if(count($data) > 1){
                    $basePath = $data[1];
                    $fileName = $basePath.'/800-450.png';
                    $isExist = Storage::exists($fileName);
                    if(!$isExist){
                        $media = new \stdClass;
                        $media->url = $basePath;
                        ImageHelper::createNewImage($media, 800, 450);
                    }
                    
                }
            }
            
        }
       
    }

    public static function getImages($published_content, $mobile = false)
    {
        $images = $mobile ? [] : '';
        if (isset($published_content->images) && $published_content->images) {
            $images = $published_content->images;
            $imageDatas = [];
            foreach ($images as $img) {
                if (!Str::endsWith($img->url, ['.png', '.jpeg'])) {
                    $imgUrl = Storage::url($img->url);
                    $img->url = $imgUrl;
                    $imageDatas[] = $imgUrl;
                } else {
                    $imageDatas[] = $img->url;
                }
            }

            if ($mobile) {
                $images = $imageDatas;
            }
        }

        return $images;
    }

    public static function migrateCollectionGalleryImage()
    {
        $types = [CollectionType::caseStudy];
        $collections = Collection::whereIn('collection_type', $types)->get();
        foreach($collections  as $collection){
            $jsonData = json_decode($collection->saved_content);
            $images = self::getImages($jsonData, true);
            if(count($images)){
                foreach($images as $image){
                    $data = explode('https://dbcms.s3.ap-south-1.amazonaws.com/', $image);
                    if(count($data) > 1){
                        $basePath = $data[1];
                        $fileName = $basePath.'/800-450.png';
                        $isExist = Storage::exists($fileName);
                        if(!$isExist){
                            $media = new \stdClass;
                            $media->url = $basePath;
                            ImageHelper::createNewImage($media, 800, 450);
                        }
                        
                    }
                }
            }
            
        }
       
    }

    public static function migrateFeedImages()
    {
        $feeds = CustomFeed::latest()->has('mediables')->with('mediables.media')->get();
        $ids = [];
        foreach($feeds as $feed){
            foreach($feed->mediables as $media){
                if(isset($media->media) and $media->media and $media->media->url){
                    $mediaData = $media->media;

                    $basePath = $mediaData->url;
                    $fileName = $basePath.'/800-450.png';
                    $isExist = Storage::exists($fileName);
                    $ids [] = $isExist;
                    if(!$isExist){
                        ImageHelper::createNewImage($mediaData, 800, 450);
                    }
                }


            }
        }

        return $ids ;
    }

    public static function migrateFeed()
    {
        $feeds = CustomFeed::latest()->get();
        $ids = [];
        foreach($feeds as $feed){
            try{
                $title = Str::substr($feed->title, 0, 20);
                $slugData = SlugHelper::getSlugAndNameFeed($title);
                $content = [
                    "meta" => [
                        "meta_title" => "mmm",
                        "meta_keywords" => "ddd",
                        "meta_description" => "ddedeed",
                        "og_title" => "ddfd",
                        "og_description" => "dfef",
                        "twitter_title" => "mmm",
                        "twitter_description" => "mmm"
                    ]
                ];
    
                $data = [
                    'title'          => $slugData->title,
                    'description'    => $feed->title,
                    'slug'           => $slugData->slug,
                    'saved_content'  => json_encode($content),
                ];
    
                $feed->update($data);
            }catch(\Exception $e){
                $ids []   = $feed->id;
            }
        }

        return collect($ids );
    }
    
}
