<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Enums\UserRole;
use App\Enums\RoleType;
use App\File as FileMedia;
use App\Helpers\ImageHelper;
use App\Helpers\MediaHelper;
use App\Helpers\SlugHelper;
use App\Http\Resources\Media as MediaResource;
use App\Http\Resources\MediaCollection;
use App\Jobs\ImageThumbnails;
use App\Media;
use App\Mediables;
use App\User;
use App\Vendor;
use App\Story;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Validator;
use Illuminate\Support\Facades\DB;

class MediaController extends Controller
{
    public function storeNewMedia(Request $request)
    {
        $medias = new Collection;
        $mediaHelper = new MediaHelper();
        $user = auth()->user();
        $slugHelper = new SlugHelper();

        if($user->role_id == RoleType::SuperAdmin && $request->vendor_id){
            $vendorId = $request->vendor_id ;
            if($vendorId){
                $vendor = Vendor::find($vendorId);
                if($vendor){
                    $user = User::where('id',$vendor->created_by)->first();
                }
            }
        }
        
        foreach ($request->data as $document) {
            $docName = $slugHelper->slugify(explode('.', $document['name'])[0]);
            if (! $docName) {
                $docName = \Str::random(5);
            }
            $mediaData = [
                'url'           => $document['data'],
                'name'          => $document['name'],
                'base_path'     => config('app.cms_media_path').'/'.$docName.'-'.time(),
            ];
            $media_path = $mediaHelper->saveMedia($mediaData);
            if ($media_path) {
                $data = [
                    'url'        => $mediaData['base_path'].'/default.png',
                    'created_by' => $user->id,
                    'mime_type'  => $document['type'],
                    'name'       => $document['name'],
                    'size'       => $document['size'],
                ];
                $media = Media::create($data);
                $media->url = Storage::disk('s3')->url($media->url);

                // ImageThumbnails::dispatch(Storage::disk('s3')->url($media_path), $mediaData['base_path']);
                $medias[] = $media;
            }
        }

        return $medias;
    }

    public function index()
    {
        $medias = Media::where('media_type', MediaType::CMSMedia)->latest()->get();
        foreach ($medias as $media) {
            $media->url = Storage::disk('s3')->url($media->url);
        }

        return $medias;
    }

    public function updateMediaData(Request $request, $id)
    {
        $user = auth()->user();
        $media = Media::find($id);
        if (! $media) {
            return response(['errors' => ['error' => ['Media not found']], 'status' => false, 'message' => ''], 422);
        }
        $data = [
            'name'        => $request->name,
            'alt_text'    => $request->alt_text,
            'title'       => $request->title,
            'description' => $request->description,
            'updated_by'  => $user->id,
        ];
        $media->update($data);
        $media->refresh();
        $media->url = Storage::disk('s3')->url($media->url);

        return $media;
    }

    public function storeNewFile(Request $request)
    {
        $files = [];
        $mediaHelper = new MediaHelper();
        $user = auth()->user();
        $allFiles = $request->data;

        $slugHelper = new SlugHelper();

        foreach ($allFiles as $doc) {
            // if ($doc['type'] == config('app.cms_files_type')) {
            $docName = $slugHelper->slugify(explode('.', $doc['name'])[0]);
            if (! $docName) {
                $docName = \Str::random(5);
            }
            $fileData = [
                'url'  => $doc['data'],
                'name' => $doc['name'],
                'base_path'  => config('app.cms_files_path').'/'.$docName,
            ];

            $file_path = $mediaHelper->saveFile($fileData);

            if ($file_path) {
                $data = [
                    'uuid'       => $file_path,
                    'created_by' => $user->id,
                    'mime_type'  => $doc['type'],
                    'filename'   => $doc['name'],
                    'size'       => $doc['size'],
                ];
                $file = FileMedia::create($data);
                $file->uuid = Storage::disk('s3')->url($file->uuid);
                $files[] = $file;
            }
            // }
        }

        return $files;
    }

    public function fileindex(Request $request)
    {
        $user = auth()->user();

        if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
            $files = FileMedia::where('created_by', $user->id)->get();
        } else {
            $files = FileMedia::all();
        }

        $files->map(function ($item) {
            $item->uuid = Storage::disk('s3')->url($item->uuid);
        });

        return $files;
    }

    public function updatefileData(Request $request, $id)
    {
        // $user = auth()->user();
        // $file = File::find($id);
        // $data = [
        //     'created_by' => $user->id,
        //     'mime_type'  => $doc['type'],
        //     'filename'   => $doc['name'],
        // ];
        // $file->update($data);
        // $file->refresh();
        // $file->url =  Storage::disk('s3')->url($file->url);
        // return $file;
    }

    public function deleteMedia(Request $request, $id)
    {
        $media = Mediables::where('media_id', $id)->get();
        if (count($media)) {
            return response(['errors' => ['error' => ['Media is being used in other collection']], 'status' => false, 'message' => ''], 422);
        }
        Media::find($id)->delete();

        return response(['message' =>  'Media deleted successfully', 'status' => true], 201);
    }

    /**
     * Cropper Functions.
     */
    public function storeNewCropperMedia(Request $request)
    {
        $document = $request->data;
        $mediaHelper = new MediaHelper();
        $user = auth()->user();
        if($user->role_id == RoleType::SuperAdmin && $request->vendor_id){
            $vendor = Vendor::where('id',$request->vendor_id)->first();
            $user   = User::find($vendor->created_by);
        }

        /* if($user->role_id == RoleType::Vendor || $user->role_id==RoleType::School){
            $user   = $user = auth()->user(); 
        } */
        $folderPath = config('app.cms_media_path').'/'.$document['path'];
        $mediaData = [
            'url'           => $document['data'],
            'name'          => ($document['width'] && $document['height']) ? $document['width'].'-'.$document['height'].'.png' : 'default.png',
            'base_path'     => $folderPath,
        ];
        $media_path = $mediaHelper->saveCroppperMedia($mediaData);
        if ($media_path) {
            $existFolder = Media::where('url', $folderPath)->first();
            if (! $existFolder) {
                $data = [
                    'url'        => $folderPath,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'mime_type'  => $document['type'],
                    'name'       => $document['name'],
                    'size'       => $document['size'],
                ];
                $media = Media::create($data);
                $media->full_url = Storage::url($media->url);
                $media->allFiles = Storage::files($folderPath);

                return $media;
            }
            $existFolder->update(['updated_by', $user->id]);
            $existFolder->allFiles = Storage::files($folderPath);
            $allFiles = collect($existFolder->allFiles)->map(function ($item) {
                $item = Storage::url($item);

                return $item;
            })->all();
            $existFolder->allFiles = $allFiles;

            return $existFolder;
        }

        return response(['errors' => ['error' => ['Upload Failed']], 'status' => false, 'message' => ''], 422);
    }

    public function getCropperMedias(Request $request)
    {
        $user = auth()->user();      
        $medias = Media::where('media_type', MediaType::CMSMedia)->latest();
        if ($request->search) {
            $medias = $medias->where('name', 'like', "%{$request->search}%")
                ->orWhere('alt_text', 'like', "%{$request->search}%")
                ->orWhere('title', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%");
        }

        if ($user->role_id == RoleType::SuperAdmin and $request->vendor_id ) {
            $vendor = Vendor::find($request->vendor_id);
            $user = User::find($vendor->created_by);
            $medias = $medias->where('created_by', $user->id);      
            
        }
        if ($user->role_id == RoleType::Vendor || $user->role_id==RoleType::School ) {  
            
            $userIds = [];
            $vendor = Vendor::where('created_by', $user->id)->first();
            $userIds = DB::connection('partner_mysql')->table('staff_vendor')->where('vendor_id', $vendor->id)->pluck('user_id')->toArray();
            $medias = $medias->where('created_by', $user->id)
                            ->orWhereIn('created_by', $userIds);
        }
        if($user->role_id == RoleType::VendorStaff || $user->role_id == RoleType::SchoolRepresentative){
            $vendorId = DB::connection('partner_mysql')->table('staff_vendor')->where('user_id', $user->id)->pluck('vendor_id');
            $vendor = Vendor::where('id', $vendorId)->first();
            $medias = $medias->where('created_by', $user->id)
                            ->orWhere('created_by', $vendor->created_by);
         }

        // if ($user->role_id != UserRole::SuperAdmin) {
        //     $medias = $medias->where('created_by', $user->id);
        // }

        if ($request->maxRows) {
            $medias = $medias->paginate($request->maxRows);
        } else {
            $medias = $medias->get();
        }
        $medias->data = $medias->map(function ($media) {
            $media->allFiles = Storage::files($media->url);
            $allFiles = collect($media->allFiles)->map(function ($item) {
                $item = Storage::url($item);

                return $item;
            })->all();
            $media->allFiles = $allFiles;
            $url = $media->url;
            $media->full_url = Storage::url($url);

            return $media;
        });

        return $medias;
    }

    public function download(Request $request, $id)
    {
        $document = FileMedia::where('id', $id)->first();

        if (! $document) {
            return response(['errors' => ['file' => ['file is invalid']], 'status' => false, 'message' => ''], 422);
        }

        $s3 = Storage::disk('s3');
        $name = substr($document->uuid, strrpos($document->uuid, '/') + 1);
        $file = $s3->get($document->uuid);

        return response($file)
          ->header('Content-Type', $document->mime_type)
          ->header('Content-Description', 'File Transfer')
          ->header('Content-Disposition', "attachment; filename={$name}")
          ->header('Filename', $name);
    }
}
