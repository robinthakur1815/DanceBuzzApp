<?php

namespace  App\Helpers;

use App\Collection as CollectionModel;
use App\Enums\MediaType;
use App\File;
use App\Media;
use App\Mediables;
use App\WebSection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;


class ImageHelper extends Facade
{

    public static function addImageUrl($published_content) 
    {
        $featured_image = null;
        $isImage = false;
        $data = new \stdClass;
        
        if (isset($published_content->featured_image) && $published_content->featured_image) {
            $featured_url = $published_content->featured_image->url ?? null;
            if ($featured_url and !Str::endsWith($featured_url, ['.png', '.jpeg'])) {
                if (!str_contains($featured_url, 'http')) {
                    $featured_image = Storage::url($featured_url);
                }else{
                    $featured_image = $featured_url;
                }
            } else {
                $featured_image = $featured_url;
                $isImage = true;
            }
        }

        $data->featured_image = $featured_image;
        $data->isImage = $isImage;
        return $data;
    }

    public static function replaceBase64WithUrl($content, $path, $compression = 75, $acl = 'public')
    {
        return \preg_replace_callback(
            '~src=["\']+(data:image\/([a-zA-Z]*);base64,([^\"]*?))["\']+~',
            function ($matches) use ($path, $compression, $acl) {
                $ext = strtolower($matches[2]);
                $filePath = $path.'/'.Str::random(16).'.'.$ext;
                //print $matches[1] . "\n";
                //print "$ext\n";
                $img = Image::make($matches[1])->encode($ext, $compression);
                Storage::put($filePath, $img->stream(), $acl);
                self::save($img->stream(), $filePath, $acl);

                return 'src="'.Storage::url($filePath).'"';
            },
            $content
        );
    }

    public static function generateThumbnail($path, $width = 130, $height = 130, $acl = 'public', $compression = 20)
    {
        $pathInfo = pathinfo($path);

        $img = Image::make(Storage::get($path))->fit($width, $height)->encode($pathInfo['extension'], $compression);

        $thumbnailPath = $pathInfo['dirname'].'/'.$pathInfo['filename'].'_thumbnail.'.$pathInfo['extension'];
        //Storage::put($thumbnailPath, $img->stream(), $acl);
        self::save($img->stream(), $thumbnailPath, $acl);

        return Storage::url($thumbnailPath);
    }

    public static function resize($basePath, $path, $width = 130, $height = 130, $compression = 90, $acl = 'public')
    {
        $pathInfo = pathinfo($path);

        $img = Image::make($path)->fit($width, $height)->encode($pathInfo['extension'], $compression);

        $thumbnailPath = $basePath.'/'."{$width}".'-'."{$height}.".$pathInfo['extension'];

        //Storage::disk('s3')->put($thumbnailPath, $img->stream(), $acl);
        self::save($img->stream(), $thumbnailPath, $acl);

        return Storage::url($thumbnailPath);
    }

    public static function getAllLoaderImages()
    {
        $mediables = Mediables::with('media')->whereIn('mediable_type', [\App\CustomFeed::class])->latest()->get();
        // 'App\Collection',
        $urls = [];
        if (count($mediables)) {
            foreach ($mediables  as $mediable) {
                $files = Storage::allFiles($mediable->media->url);

                if (count($files)) {
                    foreach ($files as $file) {
                        $urls[] = self::getConvertImage($mediable->media->url, $file);
                        // $urls[] = self::makeLoaderImage($mediable->media->url, $file);
                        // $urls[] = Storage::url($file);
                    }
                }
            }
        }

        return $urls;
    }

    public static function save($content, $path, $acl = 'public', $expires = 180)
    {
        Storage::disk('s3')->put(
            $path,
            $content,
            [
                'visibility' => $acl,
                'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 180)),
                'CacheControl' => 'max-age=315360000, no-transform, public',
            ]
        );

        $pathinfo = pathinfo($path);
        self::getConvertImage($pathinfo['dirname'], $path);
    }

    public static function savelocal($file, $filePath)
    {
        Storage::disk('public')->put(
            $filePath,
            fopen($file->getRealPath(), 'r+'),
            'public'
        );
    }

    public static function updateWithExpiry($path, $acl = 'public', $expires = 180)
    {
        $content = Storage::get($path);
        self::save($content, $path, $acl, $expires);
    }

    // Important: this function should be used with extrem care
    public static function updateAllMediaFiles()
    {
        $mediaFiles = Media::all();
        foreach ($mediaFiles as $media) {
            try {
                echo "Accessing files for {$media->url}\n";
                $files = Storage::files($media->url);
                var_dump($files);
                foreach ($files as $file) {
                    echo "Processing : {$file}\n";
                    self::updateWithExpiry($file);
                    echo "Updated : {$file}\n";
                }
            } catch (\Exception $ex) {
                echo "Failed : {$media->url}\n";
            }
        }
    }

    public static function createNewImageSizeForAllMedia($minId = null, $maxId = null)
    {
        $mediaFiles = Media::latest();

        if ($minId) {
            $mediaFiles = $mediaFiles->where('id', '>', $minId);
        }
        if ($maxId) {
            $mediaFiles = $mediaFiles->where('id', '<', $maxId);
        }
        $mediaFiles = $mediaFiles->get();

        foreach ($mediaFiles as $media) {
            $current_url = $media->url;
            if (substr($current_url, 0, 10) == 'cms/images' && substr($current_url, -12) == '/default.png') {
                try {
                    $media->url = str_replace('/default.png', '', $current_url);

                    self::createNewImage($media, 100, 100);
                    self::createNewImage($media, 800, 500);
                    self::createNewImage($media, 800, 450);
                    self::createNewImage($media, 320, 500);
                    self::createNewImage($media, 500, 500);
                    self::createNewImage($media, 400, 300);
                    self::createNewImage($media, 1366, 450);
                    self::createNewImage($media,300,200);

                    $media->timestamps = false;
                    $media->save();
                    echo "Success : {$media->id}  ,  {$media->url}\n";
                } catch (\Exception $ex) {
                    report($ex);
                    echo "Failed : {$media->id}  ,  {$media->url}\n";
                }
            } elseif (substr($current_url, 0, 10) == 'cms/images' && substr($current_url, -12) != '/default.png') {
                try {
                    $folderPath = 'cms/images/Media-'.now()->getTimestamp();
                    self::createDefaultImage($media, $folderPath);
                    $media->url = $folderPath;
                    $media->timestamps = false;
                    $media->save();

                    self::createNewImage($media, 100, 100);
                    self::createNewImage($media, 800, 500);
                    self::createNewImage($media, 800, 450);
                    self::createNewImage($media, 320, 500);
                    self::createNewImage($media, 500, 500);
                    self::createNewImage($media, 400, 300);
                    self::createNewImage($media, 1366, 450);
                    self::createNewImage($media,300,200);
                    echo "Success created : {$media->id}  ,  {$media->url}\n";
                } catch (\Exception $ex) {
                    report($ex);
                    echo "Failed : {$media->id}  ,  {$media->url}\n";
                }
            } else {
                echo "Other Image : {$media->id}  ,  {$media->url}\n";
            }
        }
    }

    public static function createNewImage($media, $width, $height)
    {
       try{
            // $pathinfo = pathInfo($media->name);
            // $extension = $pathinfo['extension'];
            $defaultFilePath = "{$media->url}/default.png";
            $outputFilePath = "{$media->url}/{$width}-{$height}.png";
            $img = Image::make(Storage::disk('s3')->get($defaultFilePath))->fit($width, $height);
            self::save($img->stream(), $outputFilePath);
       }catch(\Exception $e){
            report($e);
       }
    }


    public static function createDynamicUrlNewImage($media_id)
    {
        try{ 
            $media = Media::find($media_id);
            $width = 300 ;
            $height = 200 ;
            $defaultFilePath = "{$media->url}/default.png";
            $outputFilePath = "{$media->url}/{$width}-{$height}.png";
            info($outputFilePath);
            $img = Image::make(Storage::disk('s3')->get($defaultFilePath))->fit($width, $height);
            self::save($img->stream(), $outputFilePath);
            return true ;
        }catch(\Exception $e){
            report($e);
        }
    }

    public static function createDefaultImage($media, $folderPath)
    {
        $defaultFilePath = $media->url.'/default.png';
        $outputFilePath = "{$folderPath}/default.png";
        $img = Image::make(Storage::disk('s3')->get($defaultFilePath));
        self::save($img->stream(), $outputFilePath);
    }

    public function updateAllCollectionImages($minId = null, $maxId = null, $includeTrash = false)
    {
        try {
            $collections = CollectionModel::latest();
            if ($includeTrash) {
                $collections = $collections->withTrashed();
            }
            if ($minId) {
                $collections = $collections->where('id', '>', $minId);
            }
            if ($maxId) {
                $collections = $collections->where('id', '<', $maxId);
            }
            $collections = $collections->get();

            foreach ($collections  as $col) {
                $savedContent = json_decode($col->saved_content);
                if (isset($savedContent->featured_image) && $savedContent->featured_image) {
                    $savedContent->featured_image = $this->getMediaObject($savedContent->featured_image->id);
                }
                if (isset($savedContent->author_image) && $savedContent->author_image) {
                    $savedContent->author_image = $this->getMediaObject($savedContent->author_image->id);
                }
                if (isset($savedContent->images) && $savedContent->images) {
                    $imageIds = [];
                    $imageIds = array_map(function ($media) {
                        return $media->id ? $media->id : null;
                    }, $savedContent->images);
                    $savedContent->images = $this->getMultipleMediaObjects($imageIds);
                }
                $col->saved_content = json_encode($savedContent);

                if ($col->published_content) {
                    $col->published_content = json_encode($savedContent);
                }
                $col->timestamps = false;

                $col->save();

                echo "Success : {$col->id}\n";
            }

            return true;
        } catch (\Exception $ex) {
            echo "Error : {$ex}\n";
        }
    }

    private function getMediaObject($id)
    {
        $image = Media::where('id', $id)->first();
        if ($image) {
            $image->full_url = Storage::url($image->url);
        }

        return $image;
    }

    private function getMultipleMediaObjects($ids)
    {
        $images = Media::whereIn('id', $ids)->get();
        $images = $images->map(function ($media) {
            $url = $media->url;
            $media->full_url = Storage::url($url);

            return $media;
        });

        return collect($images);
    }

    public function updateAllSectionImages($minId = null, $maxId = null, $includeTrash = false)
    {
        try {
            $websections = WebSection::latest();
            if ($includeTrash) {
                $websections = $websections->withTrashed();
            }
            if ($minId) {
                $websections = $websections->where('id', '>', $minId);
            }
            if ($maxId) {
                $websections = $websections->where('id', '<', $maxId);
            }
            $websections = $websections->get();

            foreach ($websections as $section) {
                $allContent = json_decode($section->content);
                if ($allContent->image) {
                    $image = $this->getMediaObject($allContent->image->id);
                    $allContent->image = $image;
                }
                $section->content = json_encode($allContent);
                $section->timestamps = false;
                $section->save();

                echo "Success : {$section->id}\n";
            }
        } catch (\Exception $ex) {
            echo "Error : {$ex}\n";
        }
    }

    //uploading image
    public static function createUploadMediaMobileClient($file)
    {
        $name = $file->getClientOriginalName();
        $folderPath = 'cms/images/Media-'.now()->getTimestamp();
        $fileName = $folderPath.'/default.png';
        $content = $file->get();
        $mime_type = $file->getMimeType();
        self::save($content, $fileName);
        $media = Media::create([
            'name'        => $name,
            'url'         => $folderPath,
            'mime_type'   => $mime_type,
            'size'        => floor($file->getSize()),
            'media_type'  => 1,
        ]);

        $media->full_url = Storage::disk('s3')->url($folderPath);

        self::createNewImage($media, 100, 100);
        self::createNewImage($media, 800, 500);
        self::createNewImage($media, 800, 450);
        self::createNewImage($media, 320, 500);
        self::createNewImage($media, 500, 500);
        self::createNewImage($media, 400, 300);
        self::createNewImage($media, 1366, 450);

        return $media;
    }

    //uploading image
    public static function uploadFileMobileClient($file, $userId = null)
    {
        $name = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $folderPath = 'cms/story/file-'.now()->getTimestamp().'.'.$extension;
        $content = $file->get();
        $mime_type = $file->getMimeType();
        self::save($content, $folderPath);
        $media = File::create([
            'filename'    => $name,
            'uuid'        => $folderPath,
            'mime_type'   => $mime_type,
            'size'        => floor($file->getSize()),
            'created_by'  => $userId,
        ]);
        $media->full_url = Storage::url($folderPath);

        return $media;
    }

    //uploading image
    public static function uploadFileMobileClientStory($file, $filePath, $userId = null)
    {
        $name = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        // $folderPath = 'cms/story/file-' . now()->getTimestamp().'.'.$extension;
        $mime_type = $file->getMimeType();
        // self::savelocal($file, $folderPath);
        $media = File::create([
            'filename'    => $name,
            'uuid'        => $filePath,
            'mime_type'   => $mime_type,
            'size'        => floor($file->getSize()),
            'created_by'  => $userId,
        ]);

        return $media;
    }

    //uploading image
    public static function uploadFileMobileFeedBackClient($file)
    {
        $name = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $folderPath = 'cms/feedback/file-'.now()->getTimestamp().'.'.$extension;
        $content = $file->get();
        $mime_type = $file->getMimeType();
        self::save($content, $folderPath);
        $media = Media::create([
            'name'        => $name,
            'url'         => $folderPath,
            'mime_type'   => $mime_type,
            'size'        => floor($file->getSize()),
            'media_type'  => MediaType::UserMedia,
        ]);
        $media->full_url = Storage::url($folderPath);

        return $media;
    }

    public static function compress_png($path_to_png_file, $max_quality = 1)
    {
        $min_quality = 0;
        $compressed_png_content = shell_exec("pngquant --quality=$min_quality-$max_quality - < ".escapeshellarg($path_to_png_file));

        return $compressed_png_content;
        if (! $compressed_png_content) {
            throw new \Exception('Conversion to compressed PNG failed. Is pngquant 1.8+ installed on the server?');
        }

        return $compressed_png_content;
    }

    public static function getConvertImage($basePath, $path)
    {
        try {
            $contains = Str::contains($path, 'loader-');
            if ($contains) {
                Storage::disk('s3')->delete($path);

                return false;
            }

            $pathInfo = pathinfo($path);
            $thumbnailPath = $basePath.'/loader-'."{$pathInfo['filename']}".'.png';

            $stream = Storage::disk('s3')->get($path);
            $width = Image::make($stream)->width();
            $height = Image::make($stream)->height();

            $img = Image::make($stream)->resize($width * 0.1, $height * 0.1);

            Storage::disk('s3')->put(
                $thumbnailPath,
                $img->stream(),
                [
                    'visibility' => 'public',
                    'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 180)),
                    'CacheControl' => 'max-age=315360000, no-transform, public',
                ]
            );

            return [$width * 0.1, $height * 0.1];
        } catch (\Throwable $th) {
            return false;
        }
    }

    public static function makeLoaderImage($basePath, $path, $acl = 'public')
    {
        $contains = Str::contains($path, 'loader-');
        if ($contains) {
            return $contains;
        }

        $pathInfo = pathinfo($path);
        $thumbnailPath = $basePath.'/loader-'."{$pathInfo['filename']}".'.png';

        // $img = Image::make(Storage::disk('s3')->get($defaultFilePath))->resize($width, $height);

        $newPath = storage_path('app/public').'/'.$thumbnailPath;
        $thumbnailPath = $basePath.'/loader-'."{$pathInfo['filename']}".'.png';
        $compress = self::compress_png($newPath);
        unlink($newPath);
        if ($compress) {
            Storage::disk('s3')->put(
                $thumbnailPath,
                $compress,
                'public'
            );
        }
    }
}
