<?php

namespace App\Helpers;

use URL;
use App\User;
use App\Story;
use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Collection;
use App\Enums\StoryType;
use App\Model\Certificate;
use App\Helpers\ImageHelper;
use App\Enums\CollectionType;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;

class MediaHelper extends Facade
{
    public function saveMedia($storeMediaData)
    {
        $file_data = $storeMediaData['url'];

        // $file_name =  $storeMediaData['base_path'] . '/default.' . explode('/', explode(':', substr($file_data, 0, strpos($file_data, ';')))[1])[1];
        $file_name = $storeMediaData['base_path'] . '/default.png';
        @list($type, $file_data) = explode(';', $file_data);
        @list(, $file_data) = explode(',', $file_data);
        $imageHelper = new ImageHelper();

        $data = $imageHelper->save(base64_decode($file_data), $file_name);
        // \Storage::disk('s3')->put($file_name, base64_decode($file_data), 'public');

        return $file_name;
    }

    public function decodeBase64File($image)
    {
        $path = parse_url($image, PHP_URL_PATH);
        $data = Storage::get($path);
        $extension = pathinfo($image, PATHINFO_EXTENSION);
        $folderPath = config('app.temp_folder_path');
        if (!file_exists(public_path() . $folderPath)) {
            mkdir(public_path() . $folderPath);
        }
        $fileName = $folderPath . \Str::random(5) . '.' . $extension;
        $tmpFile = public_path() . "{$fileName}";
        file_put_contents($tmpFile, $data);
        $replacedPath = str_replace(public_path(), '', $tmpFile);

        return URL::to('/') . "{$fileName}";
    }

    public function saveFile($storeMediaData)
    {
        $file_data = $storeMediaData['url'];

        $file_name = $storeMediaData['base_path'] . '/' . time() . '.' . explode('/', explode(':', substr($file_data, 0, strpos($file_data, ';')))[1])[1];
        @list($type, $file_data) = explode(';', $file_data);
        @list(, $file_data) = explode(',', $file_data);

        // \Storage::disk('s3')->put($file_name, base64_decode($file_data), [
        //     'visibility' => 'public',
        //     'Expires' => gmdate('D, d M Y H:i:s \G\M\T', time() + (60 * 60 * 24 * 180)),
        //     'CacheControl' => 'max-age=315360000, no-transform, public',
        // ]);
        \Storage::disk('s3')->put($file_name, base64_decode($file_data), 'public');

        return $file_name;
    }

    /**
     * Cropper functions.
     */
    public function saveCroppperMedia($storeMediaData)
    {
        $file_data = $storeMediaData['url'];
        $file_name = $storeMediaData['base_path'] . '/' . $storeMediaData['name'];
        @list($type, $file_data) = explode(';', $file_data);
        @list(, $file_data) = explode(',', $file_data);
        $imageHelper = new ImageHelper();
        $data = $imageHelper->save(base64_decode($file_data), $file_name);

        return $file_name;
    }

    public static function uploadCertificate()
    {

        $certificates = Certificate::whereNotNull('student_id')->latest()->get();

        $users = [];

        foreach ($certificates as $certificate) {
            // get The User

            $user = User::with('student.school')->find($certificate->student_id);

            if ($user) {

                $user_id = $user->id;

                $allUserStories = Story::where('student_user_id', $user_id)/* ->whereIn('status', [StoryType::Submitted, StoryType::ShortListed, StoryType::Winner]) */->pluck('campaign_id')->toArray();

                foreach ($allUserStories as $story) {

                    $certificateData = Certificate::where('student_id', $user_id)->where('campaign_id', $story)->first();

                    if ($certificateData && self::certificatePDF($user,$certificateData,$story)) {
                        print "{$user->name} - {$user->id}  Campaign - {$story} uploaded\n";
                    } else {
                        print "{$user->name} - {$user->id} Campaign - {$story} failed\n";
                    }

                }

            }

        }

        return 'uploaded successfully';

    }

    public static function certificatePDF($user,$certificate,$story)
    {

        if ($certificate) {

            $name = $certificate->student_name;
            $date_of_issue = Carbon::parse($certificate->issue_date)->format('d-m-Y');
            $issuedate = $date_of_issue;
            $data = [
                'created_at' => $issuedate,
                'name' => $name,
                'date' => $issuedate,
            ];
            $dompdf = new Dompdf();
            $dompdf->loadHtml(view('exports.entry_certificate', $data));

            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4', 'landscape');

            // Render the HTML as PDF
            $dompdf->render();

            $fileName = "{$user->name}_{$user->username}_{$story}_entry_certificate.pdf";

            $fileUpload = false;

            if ($user && isset($user->student) && isset($user->student->school)) {
                $school = $user->student->school;

                if ($school->name) {

                    \Storage::disk('s3')->put("certificates/{$school->name}/{$fileName}", $dompdf->output(), 'public');

                    $url = \Storage::disk('s3')->url("certificates/{$school->name}/{$fileName}");


                    $certificate->url = $url ;
                    $certificate->save();

                   

                    $fileUpload = true;

                }

                else {

                    \Storage::disk('s3')->put("certificates/{$fileName}", $dompdf->output(), 'public');
    
                    $url = \Storage::disk('s3')->url("certificates/{$fileName}");
    
                    $certificate->url = $url ;
                    $certificate->save();
    
                    $fileUpload = true;
    
                }

            } else {

                \Storage::disk('s3')->put("certificates/{$fileName}", $dompdf->output(), 'public');

                $url = \Storage::disk('s3')->url("certificates/{$fileName}");

                $certificate->url = $url ;
                $certificate->save();

                $fileUpload = true;

            }

            return $fileUpload;

        }

    }

    public static function uploadColorthonCertificate()
    {
        // Getting Colorthon Campaign Submission

        //Finding Campaign Type Colorthon

        $colorthon = Collection::where('collection_type', CollectionType::campaignsType)->where('slug', 'colorothon')->first();

        # return $colorthon ;

        if ($colorthon) {

            #Finding all Campaigns of Colorthon

            $allCampaigns = Collection::where('collection_type', CollectionType::campaigns)->whereJsonContains('published_content->campaign_type', ['title' => $colorthon->title])->whereIn('slug',['art-treat','all-about-shades','creative-streak'])->whereNull('deleted_at')->pluck('id')->toArray();
            $allUserStories = Story::whereIn('campaign_id', $allCampaigns)->whereNull('deleted_at')->whereBetween('created_at', ['2021-11-14', '2021-12-15'])->get();

            #return count($allUserStories) ;

            if ($allUserStories && count($allUserStories) > 0) {

                foreach ($allUserStories as $story) {

                    $user = User::with('student.school')->find($story->student_user_id);

                    if ($user) {

                        $user_id = $user->id;

                        $certificateData = Certificate::where('student_id', $user_id)->where('campaign_id', $story->campaign_id)->first();

                        if (!$certificateData) {
                            $certificateData = new Certificate();
                            $certificateData->student_id = $user->id;
                            $certificateData->student_name = $user->name;
                            $certificateData->campaign_id = $story->campaign_id;
                            $certificateData->certificate_type = "consultation";
                            $certificateData->issue_date = Carbon::parse($story->created_at)->format('Y/m/d');
                            $certificateData->save();
                        }

                        if ($certificateData && self::colorothonCertificatePDF($user, $certificateData, $story->campaign_id)) {
                            break ;
                            print "{$user->name} - {$user->id}  Campaign - {$story->campaign_id} uploaded\n";
                        } else {
                            print "{$user->name} - {$user->id} Campaign - {$story->campaign_id} failed\n";
                        }

                    }
                }

            }

        }

        return 'uploaded successfully';

    }

    public static function colorothonCertificatePDF($user,$certificate,$story)
    {

        if ($certificate) {

            $name = $certificate->student_name;
            $date_of_issue = Carbon::parse($certificate->issue_date)->format('d-m-Y');
            $issuedate = $date_of_issue;
            $data = [
                'created_at' => $issuedate,
                'name' => $name,
                'date' => $issuedate,
            ];
            $dompdf = new Dompdf();
            $dompdf->loadHtml(view('exports.colorothon_entry_certificate', $data));

            // (Optional) Setup the paper size and orientation
            $dompdf->setPaper('A4', 'landscape');

            // Render the HTML as PDF
            $dompdf->render();

            $fileName = "{$user->name}_{$user->username}_{$story}_entry_certificate.pdf";

            $fileUpload = false;

            if ($user && isset($user->student) && isset($user->student->school)) {
                $school = $user->student->school;

                if ($school->name) {

                    \Storage::disk('s3')->put("certificates/{$school->name}/{$fileName}", $dompdf->output(), 'public');

                    $url = \Storage::disk('s3')->url("certificates/{$school->name}/{$fileName}");


                    $certificate->url = $url ;
                    $certificate->save();

                   

                    $fileUpload = true;

                }

                else {

                    \Storage::disk('s3')->put("certificates/{$fileName}", $dompdf->output(), 'public');
    
                    $url = \Storage::disk('s3')->url("certificates/{$fileName}");
    
                    $certificate->url = $url ;
                    $certificate->save();
    
                    $fileUpload = true;
    
                }

            } else {

                \Storage::disk('s3')->put("certificates/{$fileName}", $dompdf->output(), 'public');

                $url = \Storage::disk('s3')->url("certificates/{$fileName}");

                $certificate->url = $url ;
                $certificate->save();

                $fileUpload = true;

            }

            if ($user->email) {

                $guardian = $user->student->guardians && isset($user->student->guardians) && count($user->student->guardians) >0 ? $user->student->guardians[0] : '';
                info($user);
                info($certificate->url);
                info($guardian->user->email);
                if($guardian && isset($guardian->user->email) && !str_ends_with($guardian->user->email,'yopmail.com')){
                    \Mail::to($user->email)->send(new \App\Mail\StoryCertificate($user->id, $story,$certificate->url));
                }
                
             }

            return $fileUpload;

        }

    }
}
