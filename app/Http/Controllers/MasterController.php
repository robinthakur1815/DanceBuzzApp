<?php

namespace App\Http\Controllers;

use App\Country;
use App\Enums\PublishStatus;
use App\WebSetting;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MasterController extends Controller
{
    public function getAllCountries()
    {
        $countries = Country::all();

        return $countries;
    }

    public function saveSettingsData(Request $request)
    {
        $user = auth()->user();
        $attributes = [
            'title'           => $request->title,
            'created_by'      => $user->id,
            'updated_by'      => $user->id,
            'status'          => PublishStatus::Submitted,
            'collection_type' => $request->type,
        ];

        if ($request->start_date) {
            $saved_content['start_date'] = $request->start_date;
        }
    }

    public function getSettingsData(Request $request)
    {
        // $saved_content = $this->saveRequestContent($request);
        $datas = WebSetting::all();
        foreach ($datas as $data) {
            if ($data->setting_value != null) {
                // $data['setting_value'] = json_decode($data['setting_value']);
                $data->setting_value = json_decode($data->setting_value);
            }
        }

        return $datas;
    }

    public function saveSettingPageData(Request $request)
    {
        // $datas = WebSetting::all();
        foreach ($request->collections as $key => $ad) {
            $ad['value'] = json_encode($ad['value']);
            DB::table('web_settings')
                ->where('id', $ad['id'])
                ->update(['setting_value' => $ad['value']]);
        }

        return $request;
    }

    public function downloadFile($url)
    {
        return Storage::download(decrypt($url));
    }
}
