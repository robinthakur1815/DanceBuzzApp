<?php

namespace App\Http\Controllers;

use App\Category;
use App\CategoryGroup;
use App\Enums\CollectionType;
use App\Enums\UserRole;
use App\Helpers\SlugHelper;
use App\Http\Resources\CategoryGroupCollection;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Validator;

class CategoryController extends Controller
{
    /**
     * Get All Categories.
     */
    public function getAllCategories($type = null)
    {
        $categories = Category::with('createdBy')->latest();
        if ($type != null) {
            $categories = $categories->where('collection_type', $type);
        }
        $categories = $categories->get();

        return $categories;
    }

    public function getSingleCategory($id)
    {
        $category = Category::with('medias')->find($id);
        if ($category->medias && count($category->medias) > 0) {
            $category->medias[0]->url = Storage::disk('s3')->url($category->medias[0]->url);
        }

        return $category;
    }

    public function getPaginateCategories(Request $request)
    {
        $categories = Category::latest();
        $isMobile = $request->mobile;
        if (! $isMobile) {
            $categories = $categories->with('createdBy');
        }
        if ($isMobile) {
            $types = [CollectionType::events, CollectionType::workshops];
            $categories = $categories->whereIn('collection_type', $types);
        } 
        if ($request->search) {
            $categories = $categories->where('name', 'like', "%{$request->search}%");
        }
        if ($request->type) {
            $categories = $categories->where('collection_type', $request->type);
        }

        if ($request->withTrashed) {
            $categories = $categories->withTrashed();
        }

        if ($request->isTrashed) {
            $categories = $categories->onlyTrashed();
        }
        if ($request->maxRows) {
            $categories = $categories->paginate($request->maxRows);
        } else {
            $categories = $categories->get();
        }

        if ($isMobile) {
            $categories->getCollection()->transform(function ($category) {
                $data = [
                    'id'   => $category->id,
                    'name' => Str::title($category->name),
                ];

                return $data;
            });
        }

        return $categories;
    }

    /**
     * Save Category.
     */
    public function saveCategory(Request $request)
    {
        $user = auth()->user();

        if (! $user) {
            $user = User::where('role_id', UserRole::SuperAdmin)->where('is_active', true)->first();
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:200',
        ]);
        // $request->validate([
        //     'name' => 'required',
        // ]);
        if ($validator->fails()) {
            return response(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }

        $slugHelper = new SlugHelper();
        $slug = $slugHelper->slugify($request->name);
        if (! $request->id) {
            $slugs = Category::withTrashed()->where('slug', $slug)->first();
            if ($slugs) {
                return response(['errors' =>  ['alreadyExist' => ['already Exist with same name.']], 'status' => false, 'message' => ''], 422);
            }
        }

        $attributes = [
            'name' =>  $request->name,
            'slug' => $slug,
            'parent_id' =>  $request->parent_id,
            'updated_by' => $user->id,
        ];
        if (isset($request->type)) {
            $attributes['collection_type'] = $request->type;
        }

        if ($request->id) {
            $category = Category::find($request->id);
            $category->update($attributes);
            $category->save();
        } else {
            $attributes['created_by'] = $user->id;
            $category = Category::create($attributes);
        }
        if (isset($request->featured_image) && $request->featured_image) {
            $category->mediables()->delete();
            $category->mediables()->create([
                'media_id' => $request->featured_image['id'],
                'name' => $request->featured_image['name'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }
        $category->load('createdBy');

        return $category;
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::find($id);
        $category->update(['name' => $request->name, 'collection_type' => $request->type, 'updated_by' => auth()->id()]);

        return $category;
    }

    public function deleteCategory(Request $request, $id)
    {
        $user = auth()->user();
        Category::where('id', $id)->delete();

        return response(['message' =>  'Category deleted successfully', 'status' => false], 200);
    }

    public function deleteAllCategory(Request $request)
    {
        $user = auth()->user();
        $collectionIds = $request->collectionIds;
        foreach ($collectionIds as $id) {
            $collection = Category::where('id',$id )->first();    
            if($collection){
                $collection->delete();
            }   
        }
        return response(['message' =>  'Categorys deleted successfully', 'status' => false], 200);
    }
    public function restoreAllCategory(Request $request)
    {
        $user = auth()->user();
        $collectionIds = $request->collectionIds;
        foreach ($collectionIds as $id) {
            $collection = Category::withTrashed()->where('id',$id )->first();    
            if($collection){
                $collection->restore();
            }  
        }
        return response(['message' =>  'Categorys restore successfully', 'status' => false], 200);
    }

    public function restoreCategory(Request $request, $id)
    {
        $user = auth()->user();
        Category::where('id', $id)->restore();

        return response(['message' =>  'Category restore successfully', 'status' => false], 200);
    }

    public function saveCategoryGroup(Request $request)
    {
        $user = auth()->user();
        $slugHelper = new SlugHelper();
        $slug = $slugHelper->slugify($request->name);

        $alreadyExist = CategoryGroup::where('slug', $slug)->first();
        if ($alreadyExist && $alreadyExist->id != $request->id) {
            return response(['errors' => ['error' => ['Category group already exist']], 'status' => false, 'message' => ''], 422);
        }

        $attributes = [
            'name' => $request->name,
            'slug' => $slug,
            'updated_by' => $user->id,
        ];

        if (isset($request->collection_type)) {
            $attributes['collection_type'] = $request->collection_type;
        }

        if ($request->id) {
            $group = CategoryGroup::find($request->id);
            $group->update($attributes);
            $group->save();
        } else {
            $attributes['created_by'] = $user->id;
            $group = CategoryGroup::create($attributes);
        }
        $group->load('categories');
        $catIds = [];
        if ($request->categories) {
            $catIds = array_map(function ($cat) {
                return $cat['id'];
            }, $request->categories);
            $group->categories()->sync($catIds);
        }

        return $group;
    }

    public function getCategoryGroupList(Request $request)
    {
        $groups = CategoryGroup::with('categories')->latest();
        if ($request->search) {
            $groups = $groups->where('name', 'like', "%{$request->search}%");
        }
        if ($request->maxRows) {
            $groups = $groups->paginate($request->maxRows);
        } else {
            $groups = $groups->get();
        }

        return new CategoryGroupCollection($groups);
    }

    public function deleteCategoryGroup(Request $request)
    {
        $group = CategoryGroup::where('id', $request->id)->first();
        $group->categoryPivots()->delete();
        $group->delete();

        return response(['message' =>  'Category Group deleted successfully', 'status' => false], 200);
    }
}
