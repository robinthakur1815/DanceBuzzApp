<?php

namespace App\Imports;

use App\Story;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StoryImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)

    {

        // $guardian = DB::connection('mysql2')->table('users')
        // ->where('email', $row['guardian_email'])
        // ->first();
        
        $collection = DB::connection('partner_mysql')->table('students')
                          ->where('name', $row['name'])->where('meta->gurdian_phone', $row['guardian_phone'])
                          ->first();

       // $loc = Storage::get('\public\img');  
       

        if($row['grade'] <= config('app.grade.fifth'))
        {
            $campaignId = config('app.first_to_fifth');
        }elseif($row['grade'] >= config('app.grade.sixth') and $row['grade'] <= config('app.grade.eight')){
            $campaignId = config('app.sixth_to_seventh');
        }else{
            $campaignId = config('app.eight_above');
        }

        $metaData = [
        'sub_category' => null,
        'category'     => config('app.category_id_colorthon'),
        'user'         => $collection->user_id,
        'campaign'     => $campaignId,
        'diviceinfo'   => "",
        'diviceip'     => "",
        'divicetoken'  => ""

    ];

    
    // return new Story([
    //         "name" => $row['name'],
    //         "description" => $row['description'],
    //         "guardian_email" => $row['guardian_email'],
    //         "guardian_phone" => $row['guardian_phone'],
    //         "category_id" => $row['category_id'],
    //         "sub_category_id" => $row['sub_category_id'],
    //         "campaign_id" => $campaignId,
    //         "student_user_id" => $collection->user_id,
    //         "created_by" => $row['school_id'],
    //         'meta' => json_encode($metaData),
    //     ]);
    }
}
