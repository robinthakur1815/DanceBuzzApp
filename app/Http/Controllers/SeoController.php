<?php

namespace App\Http\Controllers;

use App\Collection as AppCollection;           //collection model
use App\Enums\CollectionType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SeoController extends Controller
{
    // public function getWebPagesList()
    // {
    //     $pages = config('app.pages');
    //     return $pages;
    // }

    // public function getSlugPagesList()
    // {
    //     $pages = config('app.slug_pages');
    //     return $pages;
    // }

    public function getAllBlogsListing($onlyPublished = true)
    {
        $datas = AppCollection::where('collection_type', CollectionType::blogs);

        if ($onlyPublished) {
            $datas = $datas->whereNotNull('published_content');
        }
        $datas = $datas->pluck('slug');

        $datas = $datas->map(function ($item) {
            $item = '/blog/'.$item;

            return $item;
        });

        return $datas->toArray();
    }

    public function getAllCaseStudiesListing($onlyPublished = true)
    {
        $datas = AppCollection::where('collection_type', CollectionType::caseStudy);
        if ($onlyPublished) {
            $datas = $datas->whereNotNull('published_content');
        }
        $datas = $datas->pluck('slug');

        $datas = $datas->map(function ($item) {
            $item = '/young_xpert/'.$item;

            return $item;
        });

        return $datas->toArray();
    }

    /**
     * function to get the list of.
     */
    public function getAllCollectionSlugListing($collectionName, $onlyPublished = true)
    {
        if ($collectionName == 'blogs') {
            $collectionId = CollectionType::blogs;
        } elseif ($collectionName == 'young_xperts') {
            $collectionId = CollectionType::caseStudy;
        } elseif ($collectionName == 'events') {
            $collectionId = CollectionType::events;
        }
        $datas = AppCollection::latest();

        if ($collectionName == 'events') {
            // $datas = $datas->whereIn('collection_type', [CollectionType::events, CollectionType::workshops, CollectionType::classes]);
            $endDate = now()->format('Y/m/d');
            $datas = $datas->whereNotNull('published_content->end_date')->where('published_content->end_date', '>=', $endDate);
        }
        $datas = $datas->where('collection_type', $collectionId);

        if ($onlyPublished) {
            $datas = $datas->whereNotNull('published_content');
        }
        $datas = $datas->pluck('slug');

        // return $collectionId;

        $datas = $datas->map(function ($item) use ($collectionId) {
            if ($collectionId == CollectionType::blogs) {
                $item = '/blog/'.$item;
            } elseif ($collectionId == CollectionType::caseStudy) {
                $item = '/young_xpert/'.$item;
            } elseif ($collectionId == CollectionType::classes) {
                $item = '/class/'.$item;
            } elseif ($collectionId == CollectionType::events) {
                $item = '/event/'.$item;
            } elseif ($collectionId == CollectionType::news) {
                $item = '/news/'.$item;
            } elseif ($collectionId == CollectionType::workshops) {
                $item = '/workshop/'.$item;
            }

            return $item;
        });

        return $datas->toArray();
    }
}
