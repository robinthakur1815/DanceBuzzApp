<?php

namespace App\Http\Controllers\Api;

use DB;
use PDF;
use Storage;
use App\File;
use App\User;
use App\Story;
use Validator;
use App\Vendor;
use App\Lib\Util;
use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Collection;
use App\UserProfile;
use App\Model\Student;
use App\Enums\RoleType;
use App\Jobs\UploadStory;
use App\Enums\StoryStatus;
use App\File as FileModel;
use App\Model\Certificate;
use App\Enums\CampaignType;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;
use App\Helpers\MediaHelper;
use App\Helpers\StoryHelper;
use App\Imports\StoryImport;
use Illuminate\Http\Request;
use App\Enums\CollectionType;
use App\Adapters\StoryAdapter;
use App\Exports\StoriesExport;
use App\Mail\FileRequestEmail;
use Illuminate\Validation\Rule;
use App\Jobs\StorySubmissionMail;
use App\Http\Resources\MobileData;
use App\Model\FileDownloadRequest;
// use Illuminate\Support\Facades\Storage;
use App\Helpers\NotificationHelper;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Resources\StoryResource;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\SubmittedStoryMail;
use App\Http\Resources\MobileDataCollection;
use App\Http\Resources\StoryResourceCollection;
use Illuminate\Support\Facades\Storage as FacadesStorage;

class ApiStoryController extends Controller
{
    protected $storyAdapter;

    public function __construct(StoryAdapter $storyAdapter)
    {
        $this->storyAdapter = $storyAdapter;
    }

    public function uploadTest(Request $request)
    {
        // $newFile, $this->user, $this->story

        $fileName = \Storage::disk('s3public')->temporaryUrl(
            'cms/story/file-51cb1f9d-88a5-5173-8157-f2c69fd55f47-1588861839674.mp4', now()->addDays(2)
        );

        return $fileName;
        $user = \App\User::first();
        $file = $request->file('file');
        $story = Story::first();
        $profile = UserProfile::first();
        $guardianEmail = 'vipin@bluelupin.com';
        // $path  = $this->uploadlocal($file);
        // UploadStory::dispatch($path, [], [], [], "");
        $path = FileModel::latest()->first()->uuid;
        // $fileName = \Storage::disk('s3')->url($path);

        $fileName = \Storage::temporaryUrl(
            $path, now()->addMinutes(5)
        );

        $start_memory = memory_get_usage();

        $line = \Storage::disk('s3')->readStream($path);

        // $fileHandle = fopen($fileName, "r");

        //If we failed to get a file handle, throw an Exception.
        // if($fileHandle === false){
        //     throw new Exception('Could not get file handle for: ' . $fileName);
        // }
        // $line = '';
        // while(!feof($fileHandle)) {
        //     $line .= fgets($fileHandle);

        // }
        // info($line);
        // fclose($fileHandle);

        return memory_get_usage() - $start_memory;

        return $fileName;
        // $fileData = $this->uploadMedias($file, $user, $story);
        // UploadStory::dispatch($fileData, $user, $story, $profile, $guardianEmail);
    }

    public function campaigns(Request $request)
    {
        $authId = auth()->id();
        $request['user_id'] = $authId;
        $campaigns = $this->getCampaigns($request);

        return new MobileDataCollection($campaigns);
    }

    public function campaignShow(Request $request, $id)
    {
        $authId = auth()->id();
        $request['user_id'] = $authId;
        $campaign = $this->getCampaigns($request, $id);
        $campaign['isDetails'] = true;

        return new MobileData($campaign);
    }

    public function stories(Request $request)
    {
        $user = auth()->user();
        $authId = auth()->id();
        if ($user->role_id == RoleType::Student) {
            $request['user_id'] = $authId;
        } else {
            // $request['created_by'] = $authId;
            $request['user_Ids'] = $this->getStudentsIds();
        }

        $stories = $this->getStories($request);

        return new StoryResourceCollection($stories);
    }


      // Generate PDF
       public function certificatePDF(Request $request) {

        $fileName = 'entry_certificate.pdf';
        $userId = $request->id;
        $certificate = Certificate::where('student_id', $userId)->latest()->first();
       // return $certificate;die();
        $name = $certificate->student_name;
        $date_of_issue = Carbon::createFromFormat('Y-m-d H:i:s', $certificate->issue_date)->format('d-m-Y');
        $issuedate = $date_of_issue;
        $data = [
            'created_at'    => $issuedate,
            'name'  => $name,
            'date'  => $issuedate,
        ];
        $dompdf = new Dompdf();
        $dompdf->loadHtml(view('exports.entry_certificate', $data));

        // (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', 'landscape');

        // Render the HTML as PDF
        $dompdf->render();

        // Output the generated PDF to Browser
        //$dompdf->stream('demo.pdf', ['Attachment'=>false]);

        // Download the generated PDF
           return response()->streamDownload(function() use($dompdf, $fileName) {
               $dompdf->stream($fileName);
           },$fileName,
               ['Access-Control-Allow-Origin' => '*',
                   'Access-Control-Allow-Methods'=> '*',
                   'Access-Control-Allow-Header' => '*']);

      }

    public function storyShow(Request $request, $id)
    {
        $user = auth()->user();
        $authId = auth()->id();
        if ($user->role_id == RoleType::Student) {
            $request['user_id'] = $authId;
        } else {
            $request['user_Ids'] = $this->getStudentsIds();
            // $request['created_by'] = $authId;
        }

        $story = $this->getStories($request, $id);
        $story['isDetails'] = true;

        return new StoryResource($story);
    }

    public function cmsStories(Request $request)
    {
        return $this->storyAdapter->cmsStories($request);
    }

    public function cmsStoryShow(Request $request, $id)
    {
        return $this->storyAdapter->cmsStoryShow($request, $id);
    }

    public static function rejectedStories(Request $request)
    {
        $stories = Story::with('student', 'campaign', 'category', 'subCategory')->withTrashed()->latest();
        $user = auth()->user();
        if ($user->role_id == RoleType::SuperAdmin and $request->vendor_id ) {
            $vendor = Vendor::find($request->vendor_id);
            $user = User::find($vendor->created_by);
            $stories = $stories->whereHas('campaign', function ($query) use ($user) {
                       $query->where('created_by', $user->id);
                });       
            
        }
        if ($user->role_id == RoleType::Vendor ) {
           
            $stories = $stories->whereHas('campaign', function ($query) use ($user) {
                       $query->where('created_by', $user->id);
                });       
            
        }

        if (isset($request['search']) and $request['search']) {
            $searchText = $request['search'];


            $stories = $stories->where(function ($q) use ($searchText) {
                $q =
                    // Campaign Name Search
                    $q->whereHas('campaign', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    })
                    // Category Name Search
                    ->orWhereHas('category', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    })
                    // Sub category Name Search
                    ->orWhereHas('subCategory', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    });

                return $q;
            });
        }
        if (isset($request['category_id']) and $request['category_id']) {
            $stories = $stories->where('category_id', $request['category_id']);
        }

        if (isset($request['sub_category_id']) and $request['sub_category_id']) {
            $stories = $stories->where('sub_category_id', $request['sub_category_id']);
        }

        if (isset($request['campaign_id']) and $request['campaign_id']) {
            $stories = $stories->where('campaign_id', $request['campaign_id']);
        }

        if (isset($request['status']) and $request['status']) {
            $stories = $stories->where('status', $request['status']);
        }

        if (isset($request['rejected']) and $request['rejected']) {
            $stories = $stories->whereNotNull('deleted_at')->where('status',3);
        }
        if (isset($request['max_rows']) and $request['max_rows']) {
            $stories = $stories->paginate($request['max_rows']);
        } else {
            $stories = $stories->get();
        }
        if (isset($request['no_resource']) and $request['no_resource']) {
            return $stories;
        } else {
            return new StoryResourceCollection($stories);
        }
    }

    public function validateStory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id'      =>  'required|int',
            'student_user_id'  =>  'required|int',
            'campaign_id'      =>  'required|int',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }

        $user = auth()->user();

        $campaign = Collection::where('id', $request->campaign_id)->where('collection_type', CollectionType::campaigns)->first();
        if (! $campaign) {
            return response(['errors' => ['campaign' => ['campaign not found']], 'status' => false, 'message' => ''], 422);
        }

        $published_content = json_decode($campaign->published_content);
        $isCheckValidNext = true;

        if (isset($published_content->campaign_type) and $published_content->campaign_type) {
            $type_id = $published_content->campaign_type->id;
            if ($type_id == CampaignType::Open) {
                $isCheckValidNext = false;
            }
        }

        if ($user->role_id == RoleType::Guardian) {
            $user = User::find($request->student_user_id);
            if (! $user) {
                return response(['errors' => ['student' => ['student not found']], 'status' => false, 'message' => ''], 422);
            }
        }


        // if ($isCheckValidNext) {
        //     $requestAll = $request->all();
        //     $requestAll['user_id'] = $user->id;
        //     $stories = $this->getStories($requestAll);
        //     if (count($stories)) {
        //         return response(['errors' => ['story' => ['already submitted in this category/subcategory, try again']], 'status' => false, 'message' => ''], 422);
        //     }
        // }


        $campaign['get_terms'] = true;

        $campaign['isdetails'] = true;




       // $published_content= $campaign['published_content'];
        //return $published_content; die();

       // $published_data = json_decode($published_content, true);

       // return $published_data; die();

       // return $published_data;die();


        if ($isCheckValidNext) {
            // $story = Story::where('student_user_id', $request->student_user_id)->latest()->first();

            // if($story){
            //     $submit_count = Story::where('student_user_id', $request->student_user_id)->count();
            //         return response()->json(['data' => [
            //             'type' => "dfsfs"
            //         ],
            //             'previous_entry_submission_status' => $story->status,
            //             'submit_count'    => "3",
            //             'submited count' => $submit_count

            //         ]);

            // }

            $story = Story::where('student_user_id', $request->student_user_id)
            ->where('campaign_id', $request->campaign_id)
            ->where('category_id', $request->category_id)
            ->where('sub_category_id', $request->sub_category_id)
            ->latest()->first();



                if($story){
                    $story_status = $story->status == 0 ? "null" : $story->status;
                    $submit_count = Story::where('student_user_id', $request->student_user_id)
                    ->where('campaign_id', $request->campaign_id)
                    ->where('category_id', $request->category_id)
                    ->where('sub_category_id', $request->sub_category_id)
                    ->count();

                    $submission_count = $submit_count == 0 ? "null" : $submit_count;

                   // return $submit_count; die();

                    $campaign['previous_entry_submission_status'] = $story_status;
                    $campaign['submit_count']    = "3";
                    $campaign['submited_count'] = $submission_count;
                    return new MobileData($campaign);
                }

               // return $campaign;die();


        }




        return new MobileData($campaign);

        // return app('App\Http\Controllers\Mobile\ApiClientWebController')->collectionDetailPage($campaign->slug);
    }

    public function create(Request $request)
    {
        
        // try {
            $validator = Validator::make($request->all(), [
                'category_id'      =>  'required|int',
                'student_user_id'  =>  'required|int',
                'campaign_id'      =>  'required|int',
                'description'      =>  Rule::requiredIf(! $request->img_file_path  && !$request->video_file_path && !$request->doc_file_path),
                'img_file_path'    =>  Rule::requiredIf(is_null($request->description) && (!$request->video_file_path) && (!$request->doc_file_path)),
                'video_file_path'  =>  Rule::requiredIf(is_null($request->description) && (!$request->img_file_path) && (!$request->doc_file_path)),
                'doc_file_path'    =>  Rule::requiredIf(is_null($request->description) && (!$request->video_file_path) && (!$request->img_file_path)),
            ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }

        $categoryId = $request->category_id;

        $user = auth()->user();

        $student_user_id = $request->student_user_id;

        //echo $student_user_id;die();
        $catcollection = Collection::where('id', $categoryId)->where('collection_type', CollectionType::campaignCategories)->first(); // category

        if (! $catcollection) {
            return response(['errors' => ['category' => ['category not found']], 'status' => false, 'message' => ''], 422);
        }
        $subcollection = [];
        if ($request->sub_category_id) {
            $subcollection = Collection::where('id', $request->sub_category_id)->where('collection_type', CollectionType::campaignSubCategories)->first(); // sub category
            if (! $subcollection) {
                return response(['errors' => ['sub_category' => ['sub category not found']], 'status' => false, 'message' => ''], 422);
            }
        }

        $campaign = Collection::where('id', $request->campaign_id)->where('collection_type', CollectionType::campaigns)->first();

        if (! $campaign) {
            return response(['errors' => ['campaign' => ['campaign not found']], 'status' => false, 'message' => ''], 422);
        }

        $published_content = json_decode($campaign->published_content);
        $isCheckValidNext = true;

        if (isset($published_content->campaign_type) and $published_content->campaign_type) {
            $type_id = $published_content->campaign_type->id;
            if ($type_id == CampaignType::Open) {
                $isCheckValidNext = false;
            }
        }

        if ($user->role_id == RoleType::Guardian) {
            $user = User::find($request->student_user_id);
            if (! $user) {
                return response(['errors' => ['student' => ['student not found']], 'status' => false, 'message' => ''], 422);
            }
        }

    // Checks exist entry status
        $story = Story::where('student_user_id', $student_user_id)->latest()->first();
        $story_status= $story !="" ? $story->status: "";


       // return $story_status;die();

       //echo $user->id;die();

        $requestAll = $request->all();

       // $requestAll['user_id'] = $user->id;
       $requestAll['user_id'] = $student_user_id;

    // Checks participation count

        if ($isCheckValidNext) {
            $stories = $this->getStories($requestAll);
           // return $stories->status; die();
            if (count($stories) and $story_status=="1") {
                return response(['errors' => ['story' => ['already submitted in this category/subcategory, try again']], 'status' => false, 'message' => ''], 422);
            }

            if (count($stories) >=3 and $story_status=="3") {
                return response(['errors' => ['story' => ['you have exceeded in this category/subcategory, try again']], 'status' => false, 'message' => ''], 422);
            }

        }

        $profile = DB::connection('partner_mysql')->table('user_profiles')->where('user_id', $user->id)->first();

        if (! $profile) {
            return response(['errors' => ['student' => ['student not found']], 'status' => false, 'message' => ''], 422);
        }

        $metaData = [
                'sub_category' => $subcollection,
                'category'     => $catcollection,
                'user'         => $user,
                'campaign'     => $campaign,
                'diviceinfo'   => $this->getDeviceinfo($request),
                'diviceip'     => $this->getDeviceip($request),
                'divicetoken'  => $this->getDeviceToken($request)

            ];
        $data = [
                'name'              => $user->name,
                //'student_user_id'   => $user->id,
                'student_user_id'   => $student_user_id,
                'description'       => $request->description,
                'comments'          => $request->comments,
                'sub_category_id'   => $request->sub_category_id,
                'category_id'       => $request->category_id,
                'campaign_id'       => $request->campaign_id,
                'meta'              => json_encode($metaData),
                'created_by'        => auth()->id(),
            ];

        // DB::transaction(function () use (&$data, $user, $request, $profile) {
        $img_file_path = $request->img_file_path;
        $img_file_name = $request->img_file_name;
        $img_mime_type = $request->img_mime_type;
        $img_size = $request->img_size;

        $video_file_path = $request->video_file_path;
        $video_file_name = $request->video_file_name;
        $video_mime_type = $request->video_mime_type;
        $video_size = $request->video_size;

        $doc_file_path = $request->doc_file_path;
        $doc_file_name = $request->doc_file_name;
        $doc_mime_type = $request->doc_mime_type;
        $doc_size = $request->doc_size;


            $story = Story::create($data);

            // Entry submission after rejection
            // if($story_status == "3"){
            //     Story::where('student_user_id', $user->id)->update(array('status' => '1'));
            // }

        // We need a check for certificate available in payload

        //$is_certification_available = "";
        $certificate_available="";
                $campaign = Collection::find($request->campaign_id);
               // return $campaign->saved_content;die();
                if($campaign){
                    $saved_content = json_decode($campaign->saved_content, true);
                    $is_certification_available = isset($saved_content['is_certification_available']) ? $saved_content['is_certification_available'] : '0';
                    $certificate_available= $is_certification_available!="" ? $is_certification_available: "";
                    // return $certificate_available; die();

                }


        if($certificate_available=="1"){

            $models = new Certificate();
            $models->student_id = $user->id;
            $models->student_name = $user->name;
            $models->campaign_id = $request->campaign_id;
            $models->certificate_type = "consultation";
            $models->issue_date = now()->format('Y/m/d');
            $models->save();

            //Uploading Certificate to S3 
            MediaHelper::certificatePDF($user,$models,$request->campaign_id);

            //$certificate_id= $models->id;
        }


        //return $certificate_id;die();

        $guardianEmail = null;
        if ($user->email) {
            $guardianEmail = explode('-', $user->email, 2)[1];
        }

        if ($img_file_path OR $video_file_path OR $doc_file_path) {
            $fileModel = $this->uploadMedias($request, $user, $story, $profile, $guardianEmail);
//            UploadStory::dispatchNow($fileModel, $user, $story, $profile, $guardianEmail);
        }

        // if ($video_file_path and $video_file_name and $video_mime_type and $video_size) {
        //     $fileModel = $this->uploadMedias($request, $user, $story);
        //     UploadStory::dispatchNow($fileModel, $user, $story, $profile, $guardianEmail);
        // }

        // if ($doc_file_path and $doc_file_name and $doc_mime_type and $doc_size) {
        //     $fileModel = $this->uploadMedias($request, $user, $story);
        //     UploadStory::dispatchNow($fileModel, $user, $story, $profile, $guardianEmail);
        // }



        if (! $img_file_path) {
            $points = $profile->enthu_points;
            $enthu_points = $points + config('app.creative_corner_enthu_point');
            DB::connection('partner_mysql')->table('user_profiles')->where('user_id', $user->id)->update(['enthu_points' => $enthu_points]);

            // Store certificate information
        }
        if ($guardianEmail) {
           \Mail::to($guardianEmail)->send(new \App\Mail\StorySubmission($user->id, $story->id, $certificate_available, true));
        }
        // });

        return response()->json(['message' => 'Successfully added'], 200);
        // } catch (\Exception $e) {
        //     report($e);
        //     return response(['message' =>  "server error", 'status' => false], 500);
        // }
    }
    private function getDeviceinfo(\Illuminate\Http\Request $request)
    {
        if($request->header('deviceinfo')){
            $header = $request->header('deviceinfo');
        }else{
           // $header = $request->header('user-agent');
            $header = $_SERVER['HTTP_USER_AGENT'] . "true";

        }
        return $header;
    }
    private function getDeviceip()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        return $ip;
    }

    private function getDeviceToken(\Illuminate\Http\Request $request)
    {
        $header = $request->header('authorization');
        return $header;
    }

    public function deleteStory(Request $request, $id)
    {
        $request['created_by'] = auth()->id();
        $story = $this->getStories($request, $id);
        if (! $story) {
            return response(['errors' => ['story' => ['story not found']], 'status' => false, 'message' => ''], 422);
        }
        $story->delete();

        return response()->json(['message' => 'Successfully deleted'], 200);
    }

    private function uploadMedias($request, $user, $story, $profile, $guardianEmail)
    {
        $img_file_path = $request->img_file_path;
        $img_file_name = $request->img_file_name;
        $img_mime_type = $request->img_mime_type;
        $img_size = $request->img_size;

        if ($img_file_path and $img_file_name and $img_mime_type and $img_size) {

            $fileData = FileModel::create([
                'filename'    => $img_file_name,
                'uuid'        => $img_file_path,
                'mime_type'   => $img_mime_type,
                'size'        => $img_size,
                'created_by'  => $user->id,
            ]);

            $mediadata = [
                'name'       => $img_file_name,
                'file_id'    => $fileData->id,
                'created_by' => $user->id,
            ];
            $story->fileables()->create($mediadata);
            UploadStory::dispatchNow($fileData, $user, $story, $profile, $guardianEmail);

            //return $fileData;
        }

        $video_file_path = $request->video_file_path;
        $video_file_name = $request->video_file_name;
        $video_mime_type = $request->video_mime_type;
        $video_size = $request->video_size;

        if ($video_file_path and $video_file_name and $video_mime_type and $video_size) {

            $fileData = FileModel::create([
                'filename'    => $video_file_name,
                'uuid'        => $video_file_path,
                'mime_type'   => $video_mime_type,
                'size'        => $video_size,
                'created_by'  => $user->id,
            ]);

            $mediadata = [
                'name'       => $video_file_name,
                'file_id'    => $fileData->id,
                'created_by' => $user->id,
            ];
            $story->fileables()->create($mediadata);
            UploadStory::dispatchNow($fileData, $user, $story, $profile, $guardianEmail);
            //return $fileData;
        }

        $doc_file_path = $request->doc_file_path;
        $doc_file_name = $request->doc_file_name;
        $doc_mime_type = $request->doc_mime_type;
        $doc_size = $request->doc_size;

        if ($doc_file_path and $doc_file_name and $doc_mime_type and $doc_size) {

            $fileData = FileModel::create([
                'filename'    => $doc_file_name,
                'uuid'        => $doc_file_path,
                'mime_type'   => $doc_mime_type,
                'size'        => $doc_size,
                'created_by'  => $user->id,
            ]);

            $mediadata = [
                'name'       => $doc_file_name,
                'file_id'    => $fileData->id,
                'created_by' => $user->id,
            ];
            $story->fileables()->create($mediadata);
            UploadStory::dispatchNow($fileData, $user, $story, $profile, $guardianEmail);
            //return $fileData;
        }
        return $fileData;


    }

    private function getStories($request, $id = null)
    {
        $stories = Story::latest();

        if (isset($request['user_id']) and $request['user_id']) {
            $stories = $stories->where('student_user_id', $request['user_id']);
        }

        if (isset($request['created_by']) and $request['created_by']) {
            $stories = $stories->where('created_by', $request['created_by']);
        }

        if (isset($request['user_Ids']) and $request['user_Ids']) {
            $stories = $stories->whereIn('student_user_id', $request['user_Ids']);
        }

        if (isset($request['category_id']) and $request['category_id']) {
            $stories = $stories->where('category_id', $request['category_id']);
        }

        if (isset($request['sub_category_id']) and $request['sub_category_id']) {
            $stories = $stories->where('sub_category_id', $request['sub_category_id']);
        }

        if (isset($request['campaign_id']) and $request['campaign_id']) {
            $stories = $stories->where('campaign_id', $request['campaign_id']);
        }

        if ($id) {
             return $stories->where('id', $id)->first();
           // return $stories->where('id', $id)->get();
        }

        // if (isset($request['sortBy']) and $request['sortBy']) {
        //     $stories = $stories->latest();
        // }else{
        //     $stories = $stories->latest();
        // }

        if (isset($request['max_rows']) and $request['max_rows']) {
            $stories = $stories->paginate($request['max_rows']);
        } else {
            $stories = $stories->get();
        }

        return $stories;
    }

    private function getCampaigns($request, $id = null)
    {
        $campaigns = Collection::where('collection_type', CollectionType::campaigns)->latest();

        if (isset($request['user_id']) and $request['user_id']) {
            $campaigns = $campaigns->whereHas('stories', function ($query) use ($request) {
                $query->where('student_user_id', $request['user_id']);
            });
        }

        if ($id) {
            return $campaigns->where('id', $id)->first();
        }

        if (isset($request['sortBy']) and $request['sortBy']) {
            $campaigns = $campaigns->latest();
        }

        if (isset($request['max_rows']) and $request['max_rows']) {
            $campaigns = $campaigns->paginate($request['max_rows']);
        }

        $campaigns = $campaigns->get();

        return $campaigns;
    }

    public function updateStoryStatus(Request $request)
    {
        $stories = $request->storyIds;
        foreach ($stories as $story) {
            $story = Story::find($story);

            if (! $story) {
                return response(['errors' =>  ['story not Found'], 'status' => false, 'message' => ''], 422);
            }

            if ($story) {
                $story->update([
                    'status' => $request->status,
                    'reason' => $request->reason,
                ]);
            }
            if($request->status == StoryStatus::Rejected){
                $studentId = $story->student_user_id;
                $student = Student::where('user_id', $studentId)->first();
                $guardian = $student->guardians()->first();
                $userId = $guardian->user_id;
                $user = User::find($userId);
                $email = $user->email;



                \Mail::to($email)->send(new \App\Mail\RejectionStory($user, $story, $student, $request->reason));
                // NotificationHelper::rejectionStoryNotification($story, $userId);

            }

        }

        return response(['message' =>  'Status updated', 'status' => true], 200);
    }

    public function updateShoppable(Request $request)
    {
        $stories = $request->storyIds;
        foreach ($stories as $story) {
            $story = Story::find($story);

            if (! $story) {
                return response(['errors' =>  ['story not Found'], 'status' => false, 'message' => ''], 422);
            }

            if ($story) {
                $story->update([
                    'is_shoppable' => $request->is_shoppable,
                ]);
            }
        }

        return response(['message' =>  'Sshoppable mark succes', 'status' => true], 200);
    }
    public function getStudentStories(Request $request)
    {
        return $this->storyAdapter->SingleStudentStories($request);
    }

    private function getStudentsIds()
    {
        $guardian = DB::connection('partner_mysql')->table('guardians')->where('user_id', auth()->id())->first();
        $students = DB::connection('partner_mysql')->table('student_guardian')->where('guardian_id', $guardian ? $guardian->id : '')->pluck('student_id');

        $studentsIds = DB::connection('partner_mysql')->table('students')->whereIn('id', $students)->pluck('user_id');

        return $studentsIds;
    }

    public function showMystorypage()
    {
        $storyData = Story::latest()->first();
        $storyHelper = new StoryHelper();
        $story = $storyHelper->storyModelData($storyData);
        $user = User::find(2);

        return view('mail.story_submitted')->with(['story' => $story, 'user' => $user, 'status' => true]);
    }

    public function downloadRequest(Request $request)
    {
        // try {
        $fileIds = [];
        $document = [];
        $fileIds = $request->file_id;
        $storyId = $request->story_id;
        $student = Story::where('id', $storyId)->first();
        $student_name= $student->name;
        $document = FileModel::where('id', $fileIds)->get();
        if (! $document) {
            return response(['errors' => ['error' => ['file not found']], 'status' => false, 'message' => ''], 422);
        }
        $user = auth()->user();
        $authId = auth()->id();
        $guardianEmail = $user->email;
        if ($user->role_id == RoleType::Student) {
            $request['user_id'] = $authId;
            if ($user->email) {
                $guardianEmail = explode('-', $user->email, 2)[1];
            }
        } else {
            $request['user_Ids'] = $this->getStudentsIds();
        }

        $story = $this->getStories($request, $storyId);
        if (! $story) {
            return response(['errors' => ['error' => ['file not found']], 'status' => false, 'message' => ''], 422);
        }

        $story->load('fileables.file');
        $files=[];

        foreach ($story->fileables as $fileData) {
            array_push($files, $fileData);
        }

        if (! $files) {
            return response(['errors' => ['error' => ['file not found']], 'status' => false, 'message' => ''], 422);
        }

        $expireAt = now()->addDays(2);

        $urls = [];
//            foreach ($files as $allfile) {
//                array_push($urls, Storage::temporaryUrl($allfile['uuid'], now()->addDays(2))
//                );
//            }
        foreach ($story->fileables as $fileable) {
            $tempUrl = \Illuminate\Support\Facades\Storage::temporaryUrl($fileable->file->uuid,now()->addDays(6));
            array_push($urls, $tempUrl);
        }

        // $data = [
        //         'file_id'    => $fileIds,
        //         'created_by' => $authId,
        //         'expire_at'  => $expireAt,
        //         'key'        => $urls,
        //     ];

        // FileDownloadRequest::create($data);
        if ($guardianEmail) {
            $storyHelper = new StoryHelper();
            $storyData = $storyHelper->storyModelData($story);
            \Mail::to($guardianEmail)->send(new FileRequestEmail($user, $storyData, $urls, $student_name));
        }

        return response(['message' =>  'success', 'status' => true], 201);
        // } catch (\Exception $e) {
        //     report($e);
        //     return response(['message' =>  "server error", 'status' => false], 500);
        // }
    }

    public function downloadFile(Request $request, $data)
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        $decrypted = Crypt::decryptString($data);
        $decodedData = json_decode($decrypted);
        $fileId = $decodedData->file_id;
        $created_by = $decodedData->created_by;

        $document = FileModel::where('id', $fileId)->first();

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

    public function mobiledownload(Request $request, $id)
    {
        $document = FileModel::where('id', $id)->first();

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

    private function uploadlocal($file)
    {
        $path = $file->store(
            'temp/',
            'local'
        );
        // info($path);
        return $path;
    }

    public function downloadCSV(Request $request)
    {
        $stories = Story::with('user.student.school.state', 'campaign.vendor', 'category', 'subCategory', 'fileables.file','campaigntype')->latest();
        $user = auth()->user();
        if ($user->role_id == RoleType::SuperAdmin and $request->vendor_id ) {
            $vendor = Vendor::find($request->vendor_id);
            $user = User::find($vendor->created_by);
            $stories = $stories->whereHas('campaign', function ($query) use ($user) {
                       $query->where('created_by', $user->id);
                });       
            
        }
        if ($user->role_id == RoleType::Vendor ) {
           
            $stories = $stories->whereHas('campaign', function ($query) use ($user) {
                       $query->where('created_by', $user->id);
                });       
            
        }
        if (isset($request['search']) and $request['search']) {
            $searchText = $request['search'];

            $stories = $stories->where(function ($q) use ($searchText) {
                $q =
                $q->whereHas('campaign', function ($query) use ($searchText) {
                    $query->whereJsonContains('saved_content->campaign_type',['title'=>"%{$searchText}%"]);
                })
                    // Campaign Name Search
                    ->orwhereHas('campaign', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    })
                    // Category Name Search
                    ->orWhereHas('category', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    })
                    // Student Name Search
                    ->orWhereHas('student', function ($query) use ($searchText) {
                        $query->where('name', 'like', "%{$searchText}%");
                    })
                    // Sub category Name Search
                    ->orWhereHas('subCategory', function ($query) use ($searchText) {
                        $query->where('title', 'like', "%{$searchText}%");
                    });

                return $q;
            });
        }

        if (isset($request['user_id']) and $request['user_id']) {
            $stories = $stories->where('student_user_id', $request['user_id']);
        }

        if (isset($request['vendor']) and $request['vendor']) {
            $stories = $stories->whereHas('campaign', function ($query) use ($request) {
                $query->whereJson('save_content->vendor->id', $request['vendor']);
            });
        }
        /*  if (isset($request['start_date']) and $request['start_date']) {
            $stories = $stories->whereHas('campaign', function ($query) use ($request) {
                        $query->where('vendor_id', $request['vendor']);
                    });
        }  */
        if (isset($request['start_date']) and $request['start_date'] || isset($request['end_date']) and $request['end_date']) {
            $stories = $stories->whereBetween('created_at', [$request['start_date'], Carbon::parse($request['end_date'])->addDays(1)]);
        }

        if (isset($request['category_id']) and $request['category_id']) {
            $stories = $stories->where('category_id', $request['category_id']);
        }

        if (isset($request['sub_category_id']) and $request['sub_category_id']) {
            $stories = $stories->where('sub_category_id', $request['sub_category_id']);
        }

        if (isset($request['campaign_id']) and $request['campaign_id']) {
            $stories = $stories->where('campaign_id', $request['campaign_id']);
        }
        
        if (isset($request['campaigntype']) and $request['campaigntype']) {
            $stories = $stories->whereHas('campaign', function ($query) use ($request) {
                $query->where('saved_content->campaign_type->id', $request['campaigntype']);
            });
         }
        if (isset($request['is_shoppable']) and $request['is_shoppable']) {
            $stories = $stories->where('is_shoppable', $request['is_shoppable']);
        }
        if (isset($request['status']) and $request['status']) {
            $stories = $stories->where('status', $request['status']);
        }
        
        $stories = $stories->get();

        if(isset($request['school_id']) and $request['school_id']){
            $schoolId = $request['school_id'];

            foreach($stories as $story){
                if(isset($story->user->student->school['id']) and $story->user->student->school['id'] == $schoolId){
                    $story['check'] = true;
                }else{
                    $story['check'] = false;
                }
            }
            $stories = $stories->where('check', true);

        }
        if(isset($request['schoolPartner_id']) and $request['schoolPartner_id']){
            $schoolPartnerId = $request['schoolPartner_id'];
            foreach($stories as $story){
                if(isset($story->user->student['vendor_id']) and $story->user->student['vendor_id'] == $schoolPartnerId){
                    $story['check1'] = true;
                }else{
                    $story['check1'] = false;
                }

            }
        }

       // $data = StoryResource::collection($stories)->resolve();
      // return response(['data' => $stories]);
       //$data =  new StoryResourceCollection($stories);
       //return $stories;

       $restricted = $request->restricted  ? true :false ;

        if($restricted){
            $headers = [
                'Student Name',
                'Campaign Name',
                'Category Name',
                'Subcategory Name',
                'Media Url',
                'story_text',
                'status',
                
            ];

            $bodies = [];
        foreach ($stories as $data) {
            $status = "";

            if ($data->status) {
                $status = StoryStatus::getKey($data->status);
            }
            $urls = [];
            $allUrl = "";
            if (count($data->fileables)) {
                foreach ($data->fileables as $fileable) {
                    if ($fileable->file) {
                        $url = FacadesStorage::temporaryUrl(
                            $fileable->file->uuid,
                            now()->addDays(7),
                            ['ResponseContentType' => 'application/octet-stream']
                        );
                        $urls[] = $url;
                    }
                }
                if (count($urls)) {
                    $allUrl = join(",", $urls);
                }
                info($urls);
            }

            $body = [
                isset($data->user['name']) ? $data->user['name'] :'', 
                isset($data->campaign['title']) ? $data->campaign['title'] :'',
                isset($data->category['title']) ? $data->category['title']:'',
                isset($data->subCategory['title']) && $data->subCategory['title'] ? $data->subCategory['title'] : '',
                $allUrl,
                isset($data->description) ? $data->description :'N/A',
                $status,
            ];
            array_push($bodies, $body);
        }


        }

        if(!$restricted){
            $headers = [
                //'id',
                'Student Name',
                'Student Email',
                'Student Phone',
                'Student address',
                'grade',
                'section',
                'school name',
                'school city',
                'school state',
                'Campaign Name',
                'Campaign Type',
                'Vendor Name',
                'Category Name',
                'Subcategory Name',
                'Media Url',
                'story_text',
                'status',
                'Submitted on',
                'platformType',
               // 'is_shoppable',
            ];

            $bodies = [];
        foreach ($stories as $data) {
            info($data);
            $campaignType = "";
            $vendorName = "";
            $status =  "";
            if ($data->status) {
                $status = StoryStatus::getKey($data->status);
            }
            $campaignData = $data->campaign ?  json_decode($data->campaign->saved_content) : "";
            if ($campaignData) {
                $campaignType = $campaignData->campaign_type ? $campaignData->campaign_type->name : "";
                $vendorName   =  isset($campaignData->vendor)  ? $campaignData->vendor->name : "";
            }

            $urls = [];
            $allUrl = "";
            if (count($data->fileables)) {
                foreach ($data->fileables as $fileable) {
                    if ($fileable->file) {
                        $url = FacadesStorage::temporaryUrl(
                            $fileable->file->uuid,
                            now()->addDays(7),
                            ['ResponseContentType' => 'application/octet-stream']
                        );
                        $urls[] = $url;
                    }
                }
                if (count($urls)) {
                    $allUrl = join(",", $urls);
                }
            }
            $studentInfo =  isset($data->user->student ['meta']) ? json_decode($data->user->student['meta'],true) :'';
            $grade = $studentInfo && $studentInfo['grades'] ?  $studentInfo['grades']  : "Not Available" ;
            $section = $studentInfo && $studentInfo['section'] ?  $studentInfo['section']  : "Not Available" ;
            $meta = isset($data->meta) && $this->isJSON($data->meta) ? json_decode ($data->meta,true): $data->meta;
            $diviceinfo = $meta && isset($meta['diviceinfo']) ? $meta['diviceinfo'] : null;
            $platform = strpos($diviceinfo, 'platform') != false ? 'APP' :'WEBSITE';
            if($platform == 'APP'){
                $platform = strpos($diviceinfo, 'Android') != false ? 'Android' :'iOS';
            }
            $body = [
               isset($data->user['name']) ? $data->user['name'] :'',
               isset($data->user['email']) ? $data->user['email'] :'',
               isset($data->user['phone']) ? $data->user['phone'] :'',
               isset($data->user->student['address']) ? $data->user->student['address'] :'Not Available',
              $grade,
              $section,
              isset($data->user->student->school['name']) ? $data->user->student->school['name'] :'Not Available',
              isset($data->user->student->school['city']) ? $data->user->student->school['city'] :'Not Available',
              isset($data->user->student->school['state']['name']) ? $data->user->student->school['state']['name'] :'Not Available',
              isset($data->campaign['title']) ? $data->campaign['title'] :'',
             $campaignType,
             $vendorName,
             isset($data->category['title']) ? $data->category['title']:'',
             isset($data->subCategory['title']) && $data->subCategory['title'] ? $data->subCategory['title'] : '',
             $allUrl,
             isset($data->description) ? $data->description :'N/A',
             $status,
             $data->created_at->format('d/m/Y h:i A'),
             $platform,
            // $data->is_shoppable ? 'Yes' : 'No',
            ];
            array_push($bodies, $body);
        }
        }


        return Excel::download(new StoriesExport($headers, $bodies), 'story.csv');
    }

    private function isJSON($string){
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
     }
    
    



    public function ImportStory(Request $request){

        $CSVCollections = Excel::toCollection(new StoryImport, $request->file);

        //return $CSVCollections;die();

        $csv_data = json_decode($CSVCollections['0'], true);

        $folderPath=public_path('images/');

        foreach($csv_data as $row)
        {
           // $name[] = $row['name'];
            //$phone[] = $row['guardian_phone'];

            if($row['grade'] <= config('app.grade.second'))
            {
                $campaignId = config('app.colorthon_campaign_type.art_treat');
            }elseif($row['grade'] > config('app.grade.second') and $row['grade'] <= config('app.grade.seventh')){
                $campaignId = config('app.colorthon_campaign_type.all_about_shades');
            }else{
                $campaignId = config('app.colorthon_campaign_type.creative_streak');
            }

            $student = DB::connection('partner_mysql')->table('students')
            ->where('name', $row['student_name'])->where('meta->gurdian_phone', $row['guardians_phone'])
            ->first();

            $this->importStoryFromDisk($student, $campaignId, $folderPath);

        }

       // return $student;die();

        //Excel::Import(new StoryImport,$request->file);
        return "Records are imported sucessfully";

    }

    // Alternative function for image name search

    public function ImportBulkStory(Request $request){

        $CSVCollections = Excel::toCollection(new StoryImport, $request->file);

       // return $CSVCollections;die();

        $csv_data = json_decode($CSVCollections['0'], true);

        //$folderPath=public_path('images/');

        //echo $folderPath; die();

       // echo sys_get_temp_dir(); die();

        foreach($csv_data as $row)
        {
            //$name[] = $row['student_name'];
            //$phone[] = $row['guardian_phone'];

            if($row['grade'] <= config('app.grade.second'))
            {
                $campaignId = config('app.colorthon_campaign_type.art_treat');
            }elseif($row['grade'] > config('app.grade.second') and $row['grade'] <= config('app.grade.seventh')){
                $campaignId = config('app.colorthon_campaign_type.all_about_shades');
            }else{
                $campaignId = config('app.colorthon_campaign_type.creative_streak');
            }

            $student = DB::connection('partner_mysql')->table('students')
            ->where('name', $row['student_name'])->where('meta->gurdian_phone', $row['guardians_phone'])
            ->first();

           // echo $row['student_name'];die();

          $images_path= $request->images_path;

          $file_name = Util::fileSearchByNameInDirectory(request('base_dir'), $student->name);
          $failedImports = [];
          if (!$file_name)  {
              \Log::warning("No file found for student: {$student->name}");
              array_push($failedImports, $student->name);
              continue;
          }

            //print_r($file_name);die();
            $this->importStoryFromDisk($student, $campaignId, $file_name);

        }

       // return $student;die();

        //Excel::Import(new StoryImport,$request->file);
        return response()->json(["Failed" => $failedImports]);

    }




    public function imageNameSearch($grade, $name, $images_path)
    {
       // $temp_loc = sys_get_temp_dir();
       // $dirname =$temp_loc.$images_path;
         $dirname = public_path('images/'.$grade);
        //$file_name = $name.'_'.$grade.'_'.$section;
        $file_name = $name;

        $filenames = glob("$dirname/*{$file_name}*" , GLOB_BRACE);

        foreach ($filenames as $filename)
        {
            return $filename;
        }

    }

        //public function importStoryFromDisk($student, $campaignId, $folderPath,  $fileName)
        public function importStoryFromDisk($student, $campaignId, $fileName)
    {
       // return $student;die();

        // $filePath = "{$folderPath}{$fileName}";
        $filePath = "{$fileName}";
        $newStory =  new Story([
        "name" => $student->name,
        "description" => "",
        //"category_id" => json_decode($campaign->category_id, true)[0],
        "category_id" => config('app.category_id_colorthon'),
        "sub_category_id" =>  null,
        "campaign_id" => $campaignId,
        "student_user_id" => $student->user_id,
        "created_by" => $student->school_id,
        'meta' => json_encode(['user'=> $student->user_id, 'campaign' => $campaignId]),
    ]);
        $newStory->save();
        $path = pathinfo($filePath);
       $ext  = $path['extension'];
       //$ext ="pdf";
        $file_Name = $path['basename'];
        Auth::loginUsingId(51);

        $fileData = FileModel::create([
                        'filename'    => $file_Name,
                        'uuid'        => "cms/story/file-" . Str::uuid() . ".".$ext,
                        'mime_type'   => mime_content_type($filePath),
                        'size'        => filesize($filePath),
                        'created_by'  => Auth::user()->id,
                    ]);

        $mediadata = [
            'name'       => $file_Name,
            'file_id'    => $fileData->id,
            'created_by' => Auth::user()->id,
        ];
        $newStory->fileables()->create($mediadata);

        \Illuminate\Support\Facades\Storage::disk('s3')->writeStream($fileData->uuid,  fopen($filePath, 'r'));


    }
}
