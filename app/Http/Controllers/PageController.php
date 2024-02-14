<?php

namespace App\Http\Controllers;

use App\Helpers\SlugHelper;
use App\Http\Resources\CmsWebPage;
use App\Http\Resources\CmsWebPageCollection;
use App\Http\Resources\CmsWebSection;
use App\Http\Resources\CmsWebSectionCollection;
use App\Http\Resources\WebPage as PageResource;
use App\Http\Resources\WebPageCollection;
use App\Http\Resources\WebSection as WebSectionResource;
use App\Http\Resources\WebSectionCollection;
use App\WebPage;
use App\WebPageSection;
use App\WebSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Log;

class PageController extends Controller
{
    public function getAllPages(Request $request)
    {
        $user = auth()->user();
        if (! $user->can('viewAny', WebPage::class)) {
            return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
        }

        $pages = WebPage::with('sections');

        if ($request->search) {
            $pages = $pages->where('name', 'like', "%{$request->search}%");
        }
        if ($request->isTrashed) {
            $pages = $pages->onlyTrashed();
        }

        if ($request->maxRows) {
            $pages = $pages->paginate($request->maxRows);
        } else {
            $pages = $pages->latest()->get();
        }

        return new CmsWebPageCollection($pages);
    }

    public function getPageDetails($id)
    {
        $user = auth()->user();

        $page = WebPage::with('seo', 'sections')->find($id);

        if (! $page) {
            return response(['errors' => 'page not Found', 'status' => false, 'message' => ''], 422);
        }
        if (! $user->can('view', $page)) {
            return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
        }

        $data = json_decode($page->content);
        $page->content = $data->content;
        if ($page->seo && $page->seo->meta) {
            $page->meta = json_decode($page->seo->meta);
        }

        // unset($page->seo);

        return new CmsWebPage($page);
    }

    public function savePageDetails(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }
        $slugHelper = new SlugHelper();
        $slug = $slugHelper->slugify($request->name);
        $slugs = WebPage::withTrashed()->where('slug', $slug);
        if ($request->id) {
            $slugs = $slugs->where('id', '!=', $request->id);
        }
        $slugs = $slugs->first();
        if ($slugs) {
            return response(['errors' =>  ['alreadyExist' => ['already Exist with same name.']], 'status' => false, 'message' => ''], 422);
        }

        $content = [
            'name' => $request->name,
            'content' => $request->content,
            'meta' => $request->meta,
            'sections' => $request->sections,
        ];

        $data = [
            'name' => $request->name,
            'slug' => $slug,
            'content' => json_encode($content),
            'updated_by' => $user->id,
        ];

        if ($request->id) {
            $page = WebPage::find($request->id);
            if (! $page) {
                return response(['errors' => 'page not Found', 'status' => false, 'message' => ''], 422);
            }
            if (! $user->can('update', $page)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $page->update($data);
        } else {
            $data['created_by'] = $user->id;
            if (! $user->can('create', WebPage::class)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $page = WebPage::create($data);
        }

        $page->load('sectionSequence');
        if ($page->sectionSequence) {
            $page->sectionSequence()->delete();
        }

        if ($request->sections) {
            foreach ($request->sections as $key => $section) {
                $page->sectionSequence()->create(['web_section_id' => $section['id'], 'sequence' => $key + 1]);
            }
        }

        if ($request->meta) {
            $seoData = [
                'meta' => json_encode($request->meta),
                'updated_by' => $user->id,
            ];
            if ($request->id) {
                $page->seo()->delete();
                // $seo = $page->seo()->update($seoData);
            }

            $seoData['created_by'] = $user->id;
            $seo = $page->seo()->create($seoData);
        }

        return $page;
    }

    public function deleteWebPages(Request $request)
    {
        $collectionIds = $request->collectionIds;
        $user = auth()->user();
        $pages = WebPage::whereIn('id', $collectionIds)->get();
        foreach ($pages as $page) {
            if (! $user->can('delete', $page)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $page->delete();
        }

        return response(['message' =>  'Pages deleted successfully', 'status' => false], 200);
    }

    public function restoreWebPages(Request $request)
    {
        $collectionIds = $request->collectionIds;
        $user = auth()->user();
        $pages = WebPage::withTrashed()->whereIn('id', $collectionIds)->get();
        foreach ($pages as $page) {
            if (! $user->can('restore', $page)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $page->restore();
        }

        return response(['message' =>  'Sections restored successfully', 'status' => false], 200);
    }

    public function getAllSections(Request $request)
    {
        try {
            $user = auth()->user();
            if (! $user->can('viewAny', WebSection::class)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }

            $sections = WebSection::with('media', 'pages')->withCount('collections')->latest();

            if ($request->search) {
                $sections = $sections->where('name', 'like', "%{$request->search}%");
            }
            if ($request->isTrashed) {
                $sections = $sections->onlyTrashed();
            }

            if ($request->maxRows) {
                $sections = $sections->paginate($request->maxRows);
            } else {
                $sections = $sections->get();
            }

            if (isset($request->basicListing) && $request->basicListing) {
                $sections->map(function ($item) {
                    $item->basicList = true;
                });
            }

            return new CmsWebSectionCollection($sections);
        } catch (\Exception $e) {
            Log::error($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }

        // return $sections;
    }

    public function getSectionDetails($id)
    {
        $user = auth()->user();

        $section = Websection::with('media', 'pages', 'mediables.media', 'collections')->find($id);

        if (! $section) {
            return response(['errors' => 'section not Found', 'status' => false, 'message' => ''], 422);
        }
        if (! $user->can('view', $section)) {
            return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
        }

        $draftData = json_decode($section->content);
        $section->content = $draftData->content;
        $section->cta = json_decode($section->cta);

        $section->image = $section->mediables ? $section->mediables->media : null;
        if ($section->image) {
            $section->image->url = $section->image ? Storage::disk('s3')->url($section->image->url) : null;
            $section->image->full_url = $section->image->url;
        }

        unset($section->mediables);
        unset($section->media);

        return $section;
    }

    public function saveSectionDetails(Request $request)
    {
        $user = auth()->user();
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
        ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }

        $slugHelper = new SlugHelper();
        $slug = $slugHelper->slugify($request->name);

        $existingSection = WebSection::where('slug', 'like', $slug);
        if ($request->id) {
            $existingSection = $existingSection->where('id', '!=', $request->id);
        }
        $existingSection = $existingSection->first();

        if ($existingSection != null) {
            return response(['errors' => ['error' => ['Section already exist.']], 'status' => false, 'message' => ''], 422);
        }

        $content = json_encode($request->all());

        $data = [
            'name' => $request->name,
            'slug' => $slug,
            'title' => $request->title,
            'heading' => $request->heading,
            'sub_heading' => $request->sub_heading,
            'content' => $content,
            'cta' => json_encode($request->cta),
            'collections' => $request->collections,
            'alignment_type' => $request->alignment_type,
            'sequence' => $request->sequence,
            'updated_by' => $user->id,
        ];

        if ($request->id) {
            $section = WebSection::find($request->id);
            if (! $section) {
                return response(['errors' => 'section not Found', 'status' => false, 'message' => ''], 422);
            }
            if (! $user->can('update', $section)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $section->update($data);
        } else {
            $data['created_by'] = $user->id;
            if (! $user->can('create', WebSection::class)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $section = WebSection::create($data);
        }

        $image = $request->image;
        if ($image) {
            $section->load('mediables');
            $mediables = $section->mediables;

            $mediableData = [
                'name' => $image['name'],
                'media_id' => $image['id'],
                'updated_by' => $user->id,
            ];

            if ($mediables) {
                $mediables->update($mediableData);
            } else {
                $mediableData['created_by'] = $user->id;
                $section->mediables()->create($mediableData);
            }
        } else {
            $section->mediables()->delete();
        }

        $collectionIds = [];
        if ($request->collections) {
            $collectionIds = array_map(function ($cat) {
                return $cat['id'];
            }, $request->collections);
        }
        $section->collections()->sync($collectionIds);

        return $section;
    }

    public function deleteWebSections(Request $request)
    {
        $sectionIds = $request->sections;
        $user = auth()->user();
        $sections = WebSection::whereIn('id', $sectionIds)->get();
        foreach ($sections as $section) {
            if (! $user->can('delete', $section)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $section->delete();
        }

        return response(['message' =>  'Sections deleted successfully', 'status' => false], 200);
    }

    public function restoreWebSections(Request $request)
    {
        $sectionIds = $request->sections;
        $user = auth()->user();
        $sections = WebSection::withTrashed()->whereIn('id', $sectionIds)->get();
        foreach ($sections as $section) {
            if (! $user->can('restore', $section)) {
                return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
            }
            $section->restore();
        }

        return response(['message' =>  'Sections restored successfully', 'status' => false], 200);
    }

    public function SectionSlugDetail(Request $request)
    {

        // dd("fjjjj");
        $user = auth()->user();

        $section = Websection::with('media', 'mediables.media')->where('slug', $request->slug)->first();

        if (! $section) {
            return response(['errors' => 'section not Found', 'status' => false, 'message' => ''], 422);
        }
        $draftData = json_decode($section->content);
        $section->content = $draftData->content;

        $featured_image = null;

        $section->image = $section->mediables ? $section->mediables->media : null;
        if ($section->image) {
            $section->image->url = $section->image ? Storage::disk('s3')->url($section->image->url) : null;
            $section->image->full_url = $section->image->url;
            $featured_image = $section->image->url;
        }

        unset($section->mediables);
        unset($section->media);

        $updatedSectionData = [
            'heading' => $section->heading,
            'sub_heading' => $section->sub_heading,
            'description' => $section->content,
            'url' => $featured_image,

        ];

        return $updatedSectionData;
    }
}
