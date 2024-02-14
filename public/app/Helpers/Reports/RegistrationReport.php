<?php


namespace App\Helpers\Reports;


use App\Story;
use App\User;
use App\Enums\StoryStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage as FacadesStorage;

class RegistrationReport
{

    // Partner Connection: partner_mysql
    // CMS Connection: mysql
    // Auth Connection: mysql2
    public static function getSchoolRegistration($schoolId, $schoolPartnerId, $campaignId, $startDate, $endDate)
    {
        $platformType = [
            1 => "Web",
            2 => "Android",
            3 => "iOS"
        ];

        // Get the School IDS
        if ($schoolId) {
            $school = DB::connection('partner_mysql')->table('schools')
                ->select('id', 'name', 'city')
                ->where('id', $schoolId)
                ->first();


            $students = DB::connection('partner_mysql')->table('students')
                ->select('id', 'user_id', 'name', 'address', 'school_id', 'meta', 'created_at')
                ->where('school_id', $schoolId)
                ->when($startDate, function ($q, $startDate) {
                    return $q->whereDate('created_at', '>=', $startDate);
                })
                ->when($endDate, function ($q, $endDate) {
                    return $q->whereDate('created_at', '<=', $endDate);
                })
                ->get();



            //$studentIds = $students->pluck('id')->toArray();

            foreach ($students as $student) {


                $studentUser = User::where('id', $student->user_id)->first();
                $student->email = $studentUser->email;
                $student->phone = $studentUser->phone;
                if ($student->meta) {
                    $studentInfo = json_decode($student->meta, true);
                }
                $student->grade = $studentInfo && isset($studentInfo['grades']) ?  $studentInfo['grades']  : "Not Available";
                $student->section = $studentInfo && isset($studentInfo['section']) ?  $studentInfo['section']  : "Not Available";

                if ($campaignId) {
                    $story =   Story::with('campaign')
                        ->where('student_user_id', $student->user_id)
                        ->where('campaign_id', $campaignId)
                        ->latest()->first();
                } else {
                    $story = Story::with('campaign')
                        ->where('student_user_id', $student->user_id)
                        ->latest()->first();
                }
                $student->school = $school->name;
                $student->city = $school->city;

                $allUrl  = "";
                if ($story) {

                    if (count($story->fileables)) {
                        foreach ($story->fileables as $fileable) {
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
                }


                $student->device_info = '';
                $student->campaign = '';
                $student->media = $allUrl;
                $student->submitted_story = 'Pending';
                $student->submitted_on = '';
                $student->platform = '';

                if ($story) {
                    $status =  "";
                    if ($story->status) {
                        $status = StoryStatus::getKey($story->status);
                    }
                    $student->submitted_story = $status;
                    $student->submitted_on = $story->created_at->format('d/m/Y h:i A');
                    $student->campaign = $story->campaign ? $story->campaign->title : 'not availbale';
                    $meta = isset($story->meta) && self::isJSON($story->meta) ? json_decode($story->meta, true) : $story->meta;
                    $diviceinfo = $meta && isset($meta['diviceinfo']) ? $meta['diviceinfo'] : null;
                    $platform = strpos($diviceinfo, 'platform') != false ? 'APP' : 'WEBSITE';
                    if ($platform == 'APP') {
                        $platform = strpos($diviceinfo, 'Android') != false ? 'Android' : 'Ios';
                    }
                    $student->platform = $platform;
                }


                $student->registered_from = $platformType[1];
                // Get the regisration detail
                $dt = DB::connection('mysql2')->table('device_tokens')
                    ->select('platform_type')
                    ->where('user_id', $student->user_id)
                    ->first();

                if ($dt) {
                    $student->registered_from = $platformType[$dt->plateform_type];
                }

                // Remove unnecessary properties
                unset($student->registered_from);
                unset($student->id);
                unset($student->meta);
                unset($student->school_id);
                unset($student->device_info);
            }
        }

        if ($schoolPartnerId) {

            $students = DB::connection('partner_mysql')->table('students')
                ->select('id', 'user_id', 'name', 'address', 'school_id', 'meta', 'created_at')
                ->where('vendor_id', $schoolPartnerId)
                ->when($startDate, function ($q, $startDate) {
                    return $q->whereDate('created_at', '>=', $startDate);
                })
                ->when($endDate, function ($q, $endDate) {
                    return $q->whereDate('created_at', '<=', $endDate);
                })
                ->get();

            foreach ($students as $student) {

                $studentUser = User::where('id', $student->user_id)->first();
                $student->email = $studentUser->email;
                $student->phone = $studentUser->phone;
                if ($student->meta) {
                    $studentInfo = json_decode($student->meta, true);
                }
                $student->grade = $studentInfo && isset($studentInfo['grades']) ?  $studentInfo['grades']  : "Not Available";
                $student->section = $studentInfo && isset($studentInfo['section']) ?  $studentInfo['section']  : "Not Available";


                if ($campaignId) {
                    $story = Story::with('campaign')
                        ->where('student_user_id', $student->user_id)
                        ->where('campaign_id', $campaignId)
                        ->latest()->first();
                } else {
                    $story = Story::with('campaign')
                        ->where('student_user_id', $student->user_id)
                        ->latest()->first();
                }
                $vendorSchool = DB::connection('partner_mysql')->table('vendors')
                    ->select('id', 'name', 'city')
                    ->where('id', $schoolPartnerId)
                    ->first();

                $student->school = $vendorSchool->name;
                $student->city = $vendorSchool->city;
                $allUrl  = "";
                if ($story) {

                    if (count($story->fileables)) {
                        foreach ($story->fileables as $fileable) {
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
                }

                $student->device_info = '';
                $student->campaign = '';
                $student->media = $allUrl;
                $student->submitted_story = 'Pending';
                $student->submitted_on = '';
                $student->platform = '';

                if ($story) {
                    $status =  "";
                    if ($story->status) {
                        $status = StoryStatus::getKey($story->status);
                    }
                    $student->submitted_story = $status;
                    $student->submitted_on = $story->created_at->format('d/m/Y h:i A');
                    $student->campaign = $story->campaign->title;
                    $meta = isset($story->meta) && self::isJSON($story->meta) ? json_decode($story->meta, true) : $story->meta;
                    $diviceinfo = $meta && isset($meta['diviceinfo']) ? $meta['diviceinfo'] : null;
                    $platform = strpos($diviceinfo, 'platform') != false ? 'APP' : 'WEBSITE';
                    if ($platform == 'APP') {
                        $platform = strpos($diviceinfo, 'Android') != false ? 'Android' : 'Ios';
                    }
                    $student->platform = $platform;
                }


                $student->registered_from = $platformType[1];
                // Get the regisration detail
                $dt = DB::connection('mysql2')->table('device_tokens')
                    ->select('platform_type')
                    ->where('user_id', $student->user_id)
                    ->first();

                if ($dt) {
                    $student->registered_from = $platformType[$dt->plateform_type];
                }

                // Remove unnecessary properties
                unset($student->registered_from);
                unset($student->id);
                unset($student->meta);
                unset($student->school_id);
                unset($student->device_info);
            }
        }
        return $students;
    }

    public static function jsonify($content)
    {
        if (!is_string($content)) return $content;

        $content = preg_replace('/^"/', "", $content);
        $content = preg_replace('/"$/', "", $content);
        $content = stripslashes($content);
        return preg_replace('/\\"/', '"', $content);
    }

    public static function getPlatformFromDeviceInfo($str)
    {
        if (!is_string($str)) return $str;
        if (preg_match('/platform:\s*(\w+?),/', $str, $matches)) {
            return $matches[1];
        }
        return 'not available';
    }
    private static function isJSON($string)
    {
        return is_string($string) && is_array(json_decode($string, true)) ? true : false;
    }
}
