<?php

namespace  App\Helpers;

use App\Story;
use Illuminate\Support\Str;
use App\Imports\StoryImport;
use Illuminate\Support\Facades\Facade;

class StoryHelper extends Facade
{
    public function storyModelData(Story $story)
    {
        $story->load('campaign', 'category', 'subCategory');

        $storyData['created_at'] = $story->created_at;

        $storyData['campaign_type'] = null;
        $storyData['campaign_name'] = $story->campaign ? $story->campaign->title : null;
        $storyData['category_name'] = $story->category ? $story->category->title : null;
        $storyData['sub_category_name'] = $story->subCategory ? $story->subCategory->title : null;
        $storyData['description'] = $story->description ? $story->description : null;
        $storyData['comment'] = $story->comments ? $story->comments : null;
        $storyData['attachment_type'] = null;

        if ($story->campaign) {
            $campaignContent = json_decode($story->campaign->published_content);

            if ($campaignContent && $campaignContent->campaign_type && $campaignContent->campaign_type->name) {
                $storyData['campaign_type'] = $campaignContent->campaign_type->name;
            }
        }
        if ($story->category) {
            $categoryContent = json_decode($story->category->published_content);

            if ($categoryContent && $categoryContent->category_type && count($categoryContent->category_type) > 0) {
                $list = array_column($categoryContent->category_type, 'name');
                $storyData['attachment_type'] = implode(', ', $list);
            }
        }

        return $storyData;
    }

    public function uploadBulkStories()
    {
        $CSVCollections = Excel::toCollection(new StoryImport, request()->file('document'));
        $notCreatedData = [];
        $notCreatedCSVData = [];

        foreach ($CSVCollections[0] as $key => $col) {

            if ($key > 0) {

                return $col->name;
            }

        }


        return [
            'status'            => true,
            'notCreatedData'    => $notCreatedData,
            'notCreatedCSVData' => $notCreatedCSVData,
        ];
    }
}
