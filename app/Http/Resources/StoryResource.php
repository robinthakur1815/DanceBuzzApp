<?php

namespace App\Http\Resources;

use App\Story;
use App\Fileable;
use Carbon\Carbon;
use App\Enums\FileType;
use App\Enums\MimeType;
use App\Enums\StoryType;
use App\Enums\StoryStatus;
use App\Model\Certificate;
use App\Enums\CampaignType;
use Illuminate\Support\Str;
use Faker\Test\Provider\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $campaignName = $this->campaign && $this->campaign->title ? Str::title($this->campaign->title) : ' ';

        $campaign = $this->campaign ? json_decode($this->campaign->saved_content) : null;

       // return $campaign->is_certification_available;die();

        $campaignType = isset($campaign->campaign_type) && $campaign->campaign_type && $campaign->campaign_type->name ? $campaign->campaign_type->name : '';

        $campaignVendor  = isset($campaign->current_user) && $campaign->current_user && $campaign->current_user->name ? $campaign->current_user->name : '';
       // $campaignVendor  = isset($campaign->campaign_type) && $campaign->campaign_type && $campaign->campaign_type->created_by ? $campaign->campaign_type->created_by : '';
       // $meta = isset($metaData) ? json_decode($metaData) : null; 
        $meta = $this->meta ;
    
        $diviceinfo = $this->meta && isset($meta->diviceinfo) ? $meta->diviceinfo : null;
        
        $diviceip = $this->meta && isset($meta->diviceip) ? $meta->diviceip : null;
      
        $status = isset($this->status) ? StoryType::getKey($this->status) : '';

        $categoryName = $this->category ? Str::title($this->category->title) : ' ';
        $subCategoryName = $this->subCategory ? Str::title($this->subCategory->title) : ' ';
        $name = $this->student ? Str::title($this->student->name) : ' ';
        $this->load('fileables.file');
        
        $campaign_end_date = $campaign && $campaign->end_date ? ($campaign->end_date) : ' ';
        
        $filename = '';
        $mime_type = '';
        $thumbnail = '';
        $isImage = false;
        $url = '';
        $fileExists = true;
        $isProcessing = false;
        $is_shoppable = false;
        $file_id = '';
        $is_certification_available ='';

        $campaign_is_expired = $campaign_end_date < now()->format('Y/m/d') ? true:false ;
        
        $campaignExpired = $this->campaignExpiredStatus($this->campaign);

        $is_certification_available = isset($campaign->is_certification_available) && $campaign->is_certification_available ? $campaign->is_certification_available : '';
        
        // Generate entry certificate

        

        if (! $this->isDetails) {
            return [
                'id'                    => $this->id,
                'campaign_name'         => $campaignName,
                'campaignExpired'       => $campaignExpired,
                'category_name'         => $categoryName,
                'sub_category_name'     => $subCategoryName,
                'description'           => $this->description ? $this->description : '',
                'comments'              => $this->comments ? $this->comments : '',
                'created_at'            => (string) $this->created_at,
                'student_name'          => $name,
                 'diviceinfo'            => $diviceinfo,
                 'diviceip'              => $diviceip,
                 'is_shoppable'          =>  $this->is_shoppable,
                 'deleted_at'            => $this->deleted_at,
//                'filename'              => $filename,
//                'file_url'              => $url,
//                'file_id'               => $file_id,
//                'fileExists'            => $fileExists,
//                'mime_type'             => $mime_type,
//                'thumbnail'             => $thumbnail,
//                'isImage'               => $isImage,
                //'camp'                  => $campaign,
                //  'certificate'            => $this->getAllCertificate(),
                'certificate'            => $is_certification_available == true ? $this->getAllCertificate(): "",
                'campaignType'          => $campaignType,
                'campaignVendor'        => $campaignVendor,

                // 'status'                => $status
                'status'                => StoryType::getKey($this->status),
                'reason'                => isset($this->reason) ? $this->reason:'',
                'status_key'            => $this->status,
              'campaign_end_date'             => $campaign_end_date,
              'campaign_is_expired'         => $campaign_is_expired,
                'media'                 => $this->getAllMedia(),
               
            ];
        }

        if (isset($this->isDetails) && $this->isDetails) {
            $this->saved_content ? json_decode($this->saved_content, true) : null;

            return [
                'id'              => $this->id,
                'name'            => $campaignName,
                'description'     => $this->description,
                'comments'        => $this->comments,
                'is_shoppable'    => $this->is_shoppable,
                'student'         => $this->student ? $this->getStudentDetails($this->student) : null,
                'campaign'        => $this->campaign ? $this->getCampaignDetails($this->campaign, $campaign) : null,
                'categoryName'    => $categoryName,
                'subCategoryName' => $subCategoryName,
                'campaign_type'   => isset($campaign->campaign_type) && $campaign->campaign_type ? $campaign->campaign_type->name : null,
                'vendor'          => isset($campaign->vendors) && $campaign->vendors ? $campaign->vendors->name : null,
                'created_at'      => (string) $this->created_at,
                //'reason'          => $this->reason,
                'reason'          => isset($this->reason) ? $this->reason:'',
                'status'          => StoryType::getKey($this->status),
//                'filename'        => $filename,
//                'file_url'        => $url,
//                'file_id'         => $file_id,
//                'fileExists'      => $fileExists,
//                'is_processing'   => $isProcessing,
                 'campaign_end_date'             => $campaign_end_date,
                 'campaign_is_expired'         => $campaign_is_expired,
                'media'             => $this->getAllMedia(),
                'diviceip'            => $diviceip,
                'diviceinfo'            => $diviceinfo
                
            ];
        }

        return parent::toArray($request);
    }

    private function getMediaDetails(Fileable $fileData)
    {
        if (!$fileData) return [];
        if (!$fileData->relationLoaded('file'))
            $fileData->load('file');
        $file = $fileData->file;


        return [
          'name' => $fileData->name,
            'mime_type' => $file->mime_type,
            'file_type' => $this->getFileType($file->mime_type),
            'is_processing' => $this->isProcessing($file),
            'url' => Storage::temporaryUrl($file->uuid,now()->addMinutes(55)),
            'thumbnail' => $this->thumbnail($file->mime_type),
            'is_image' => Str::contains($file->mime_type, 'image'),
            'is_video' => Str::contains($file->mime_type, 'video'),
            'id' => $fileData->id,
            'file_id' => $file->id
        ];

    }

    private function getAllMedia() {
        $allMedia = [];
        foreach ($this->fileables as $fileData) {
            array_push($allMedia, $this->getMediaDetails($fileData));
        }
        return $allMedia;
    }

    private function getAllCertificate(){

        $cetificate_id=array();
        $certificates = Certificate::where('student_id', $this->student_user_id)->latest()->first();
 
        // foreach($certificates as $certificate)
        // {
        //     $cetificate_id[] = url('/download/certificate/')."/".$certificate['id'];
        // }

         if($certificates){
            $cetificate_id = url('/download/certificate/')."/".$certificates->student_id;
            return $cetificate_id;
         }   
        
    }


    private function campaignExpiredStatus($campaign)
    {
        $endDate = '';
        $status = false;

        if (! $campaign) {
            return false;
        }

        if (! isset($campaign['saved_content'])) {
            return false;
        }

        $published_content = json_decode($campaign['saved_content']);
        if (isset($published_content->end_date) and $published_content->end_date) {
            $endDate = Carbon::createFromFormat('Y/m/d', $published_content->end_date)->startOfDay();
        }

        $currentDate = now()->startOfDay();

        if ($endDate and $currentDate->gt($endDate)) {
            $status = true;
        }

        return $status;
    }

    private function thumbnail($mime_type)
    {

        $mimes = explode('/', $mime_type);
        if ($mimes[0] == FileType::Video)
            return url('/').'/images/extensions/mp4.svg';

        elseif($mimes[1] == FileType::Pdf) {
            return url('/').'/images/extensions/pdf.svg';
        }

        elseif($mimes[1] == FileType::SpreadSheet) {
            return url('/').'/images/extensions/pdf.svg';
        }

        else
            return url('/').'/images/extensions/all.svg';

    }

    private function getFileType($mime_type)
    {
        $mimes = explode('/', $mime_type);

        if (count($mimes) > 0) {
            if ($mimes[0] == 'image') {
                return MimeType::Image;
            }

            if ($mimes[0] == 'video') {
                return MimeType::Video;
            }

            if ($mimes[0] == 'audio') {
                return MimeType::Audio;
            }
        }

        return MimeType::Docs;
    }

    private function getCampaignDetails($camp, $campaign)
    {
        $updatedCampaign = [
            'title' => $camp->title,
            'content' => $campaign->content,
            'excerpt' => $campaign->excerpt,
            'sponsors' => isset($campaign->sponsors) && $campaign->sponsors ? $this->getCampaignSponsers($campaign->sponsors) : [],
        ];

        return $updatedCampaign;
    }

    private function getCampaignSponsers($sponsers)
    {
        $updatedSponsors = collect();
        foreach ($sponsers as $key => $sponser) {
            $updatedSponsors->push(
                [
                    'title' => $sponser->title,
                    'excerpt' => isset($sponser->excerpt) && $sponser->excerpt ? $sponser->excerpt : '',
                    'published_by' => $sponser->published_by,
                    'featured_image' => $sponser->featured_image,
                ]
            );
        }

        return $updatedSponsors;
    }

    private function getStudentDetails($student)
    {
        $updatedStudent = [
            'name' => $student->name,
            'email' => $student->email,
            'phone' => $student->phone,
        ];

        return $updatedStudent;
    }

    private function isProcessing($fileModel)
    {
        if (\Str::contains($fileModel->mime_type, 'video')) {
            return $fileModel->video_converted;
        }

        return true;
    }
}
