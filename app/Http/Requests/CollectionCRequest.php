<?php

namespace App\Http\Requests;

use App\Adapters\Subscription\SubscriptionAdapter;
use App\Category;
use App\Collection as DataModel;
use App\Discount;
use App\Enums\CollectionType;
use App\Enums\CouponStatus;
use App\Enums\PublishStatus;
use App\Helpers\CollectionHelper;
use App\Helpers\ImageHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\SlugHelper;
use App\Product;
use App\ProductPrice;
use App\Tag;
use Carbon\Carbon;
use DB;
use  Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectionCRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type'                  => ['required', Rule::in([CollectionType::events, CollectionType::workshops])],
            'title'                 => 'required|string|max:100',
            'excerpt'               => 'nullable',
            'highlights'            => 'nullable',
            'description'           => 'nullable',
            'featured_image'        => 'nullable|image',
            'categories'            => 'nullable|array',
            'start_date'            => 'nullable|date_format:d/m/Y',  //ISO-8601
            'end_date'              => 'nullable|date_format:d/m/Y', //ISO-8601
            'start_time'            => 'nullable|date_format:"g:i A"', //ISO-8601
            'end_time'              => 'nullable|date_format:"g:i A"', //ISO-8601,
            'city'                  => 'nullable|string',

            'price'                 => 'nullable|numeric',
            'discount_code'         => 'required_with_all:discount_start_date,discount_end_date,value',
            'discount_start_date'   => 'required_with_all:discount_code,discount_end_date,value',
            'discount_end_date'     => 'required_with_all:discount_code,discount_start_date,value',
            'value'                 => 'required_with_all:discount_code,discount_end_date,discount_start_date,value',
            'aditional_discount'    => 'required_with_all:discount_code,discount_end_date,discount_start_date,value',
            'threshold_value'       => 'required_with_all:discount_code,discount_end_date,discount_start_date,value',

            'discount_start_date'   => 'nullable|date_format:d/m/Y', //ISO-8601,
            'discount_end_date'     => 'nullable|date_format:d/m/Y', //ISO-8601,
            'value'                 => 'nullable|numeric',
            'aditional_discount'    => 'nullable|numeric',
            'is_percentage'         => 'nullable|boolean',
            'threshold_value'       => 'nullable|numeric',

            'trainer_name'          => 'required_with_all:trainer_designation,trainer_experience,trainer_description,trainer_image',
            'trainer_description'   => 'required_with_all:trainer_name',
            'trainer_designation'   => 'required_with_all:trainer_name',
            'trainer_experience'    => 'required_with_all:trainer_name',
            'trainer_image'         => 'required_with_all:trainer_name',
            'trainer_image'         => 'nullable|image',
            'trainer_description'   => 'nullable|string',
            'trainer_name'          => 'nullable|string',
            'trainer_designation'   => 'nullable|string',
            'trainer_experience'    => 'nullable|numeric',

            'tags'                  => 'nullable|array',
            'unit'                  => 'nullable|integer',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'type.in' => 'invalid request',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (! count($validator->errors())) {
                $createEventData = $this->createEvent($this);
                if (! $createEventData['status']) {
                    $validator->errors()->add('error', $createEventData['message']);
                }
            }
        });
    }

    //create event
    private function createEvent($request)
    {
        try {
            $slugHelper = new SlugHelper();
            $slug = $slugHelper->slugify($request->title);

            $slugs = DataModel::where('slug', $slug)->where('collection_type', $request->type)->first(); // if this event already exists

            if ($slugs) {
                return['status' => false, 'message' => 'already Exist with same name.'];
            }

            // only if product is paid
            if ($request->price) {
                $slug_product = Product::where('slug', $slug)->first();
                if ($slug_product) {
                    return['status' => false, 'message' => 'already Exist with same name.'];
                }
            }

            $saved_content = $this->savedContent($request->all(), $request);
            $attributes = [
                'title'               => $request->title,
                'slug'                => $slug,
                'status'              => PublishStatus::Published,
                'saved_content'       => $saved_content,
                'published_content'   => $saved_content,
                'collection_type'     => $request->type,
                'vendor_id'           => $this->vendorId,
                'published_at'        => now(),
            ];

            $productData = [
                'name'          => $request->title,
                'slug'          => $slug,
                'description'   => $request->title,
                'stock'         => $request->unit,
                'status'        => PublishStatus::Published,
                'vendor_id'     => $this->vendorId,
                'collection_id' => null,
            ];

            $tags = $request->tags;
            $categories = $request->categories;

            $endDate = $request->end_date ? Carbon::createFromFormat('d/m/Y', $request->end_date) : '';
            if ($endDate and $endDate->endOfDay()->isPast()) {
                return['status' => false, 'message' => 'end date must be equal or greater than today'];
            }

            if ($categories) {
                $categoriesIds = [];
                foreach ($categories as $catId) {
                    $categoriesIds[] = (int) $catId;
                }

                $attributes['categories'] = json_encode($categoriesIds);
                $productData['categories'] = json_encode($categoriesIds);

                $countCategories = Category::whereIn('id', $categoriesIds)->count();

                if ($countCategories != count($categoriesIds)) {
                    return['status' => false, 'message' => 'Selected category is not valid'];
                }
            }

            if ($tags) {
                $tagIds = [];
                foreach ($tags as $tagId) {
                    $tagIds[] = (int) $tagId;
                }

                $attributes['tags'] = json_encode($tagIds);
                $productData['tags'] = json_encode($tagIds);
                $countTags = Tag::whereIn('id', $tagIds)->count();
                if ($countTags != count($tagIds)) {
                    return['status' => false, 'message' => 'Selected tag is not valid'];
                }
            }

            $discountData = null;
            if ($request->value) {
                if ($request->is_percentage && $request->value >= 100) {
                    return['status' => false, 'message' => 'Discount is not valid'];
                }
                $discount_start_date = Carbon::createFromFormat('d/m/Y', $request->discount_start_date);
                $discount_end_date = $request->discount_end_date ? Carbon::createFromFormat('d/m/Y', $request->discount_end_date) : '';
                if ($discount_end_date and $discount_end_date->endOfDay()->isPast()) {
                    return['status' => false, 'message' => 'Discount end date must be equal or greater than today'];
                }
                if (! $request->is_percentage and $request->value >= $request->price) {
                    return['status' => false, 'message' => 'Discounted amount should not be greater than or equal to actual price'];
                }

                $discountData = [
                    'name'                      => $request->title,
                    'code'                      => $request->discount_code,
                    'description'               => $request->title,
                    'amount'                    => $request->value,
                    'is_percentage'             => $request->is_percentage ? true : false,
                    'start_date'                => $discount_start_date,
                    'end_date'                  => $discount_end_date,
                    'additional_threshold'      => $request->threshold_value,
                    'additional_amount'         => $request->aditional_discount,
                    'vendor_id'                 => $this->vendorId,
                    'status'                    => PublishStatus::Published,
                ];
            }
            $collection = null;
            DB::transaction(function () use ($request, $attributes, $productData, $discountData, $categories, $tags, &$collection) {
                $collection = DataModel::create($attributes);

                $productData['collection_id'] = $collection->id;

                // only if product is paid
                if ($request->price) {
                    $product = Product::create($productData);

                    $discountPrice = null;

                    if ($discountData) {
                        $discountPrice = Discount::create($discountData);
                    }

                    $productPriceData = [
                        'name'       => $request->title,
                        'price'      => $request->price,
                        'unit'       => $request->unit,
                        'status'     => CouponStatus::Published,
                        'product_id' => $product->id,
                        'vendor_id'  => $this->vendorId,
                        'discount_id'=> $discountPrice ? $discountPrice->id : null,
                    ];

                    $productPrice = ProductPrice::create($productPriceData);
                    CollectionHelper::attachDetachPrice($product, $productPrice, 'attach');
                    $sa = new SubscriptionAdapter($collection->vendor_id);
                    $amountData = $sa->process($request->price, $collection->collection_type, null);

                    $collection->update(['published_price' => $amountData['amount']]);
                }

                /*
                 * Creating Pivot for categories
                */
                if ($categories) {
                    foreach ($categories as $catId) {
                        $collection->categoryPivot()->create([
                            'category_id'     => $catId,
                            'collection_id'   => $collection->id,
                            'collection_type' => $collection->collection_type,
                        ]);
                    }
                }

                /*
                 * Creating Pivot for tags
                */
                if ($tags) {
                    foreach ($tags  as $tagId) {
                        $collection->tagPivot()->create([
                            'tag_id'          => $tagId,
                            'collection_id'   => $collection->id,
                            'collection_type' => $collection->collection_type,
                        ]);
                    }
                }

                //image upload
                if ($request->hasFile('featured_image')) {
                    $file = $request->file('featured_image');
                    $name = $file->getClientOriginalName();
                    $media = ImageHelper::createUploadMediaMobileClient($file);
                    $collection->mediables()->create([
                        'media_id' => $media->id,
                        'name'     => $name,
                    ]);
                    $request->featured_image = $media;
                }

                //upload trainer image
                if ($request->hasFile('trainer_image')) {
                    $file = $request->file('trainer_image');
                    $name = $file->getClientOriginalName();
                    $media = ImageHelper::createUploadMediaMobileClient($file);
                    $request->author_image = $media;
                }

                if ($request->hasFile('trainer_image') or $request->hasFile('featured_image')) {
                    $saved_content = $this->savedContent($request->all(), $request);
                    $attributes = [
                        'saved_content'       => $saved_content,
                        'published_content'   => $saved_content,
                    ];
                    $collection->update($attributes);
                }
            });

            if ($collection) {
                // NotificationHelper::collection($collection);
            }

            return['status' => true, 'message' => 'success'];
        } catch (\Exception $e) {
            report($e);

            return['status' => false, 'message' => 'server error'];
        }
    }

    // saved content
    private function savedContent($requestData, $request)
    {
        $startDateData = $request->start_date ?? now()->format('d/m/Y');
        $endDateData = $request->end_date ?? now()->format('d/m/Y');

        $start_date = $this->dateFormat($startDateData);
        $end_date = $this->dateFormat($endDateData);

        $requestData['author_name'] = $request->trainer_name;
        $requestData['location'] = $request->city;
        $requestData['highlights'] = $request->highlight;
        $requestData['content'] = $request->description;

        $requestData['author_designation'] = $request->trainer_designation;
        $requestData['author_description'] = $request->trainer_description;
        $requestData['author_experience'] = $request->trainer_experience;
        $requestData['start_date'] = $start_date;
        $requestData['end_date'] = $end_date;

        if ($request->featured_image) {
            $requestData['featured_image'] = $request->featured_image;
        }

        if ($request->author_image) {
            $requestData['author_image'] = $request->author_image;
        }

        if ($request->start_time) {
            $requestData['start_time'] = $this->dateTimeFormat($request->start_time);
        } else {
            $requestData['start_time'] = null;
        }

        if ($request->end_time) {
            $requestData['end_time'] = $this->dateTimeFormat($request->end_time);
        } else {
            $requestData['end_time'] = null;
        }

        if ($request->categories) {
            $categories = Category::whereIn('id', $request->categories)->with('createdBy')->get();
            $requestData['categories'] = $categories;
        }

        if ($request->tags) {
            $tags = Tag::whereIn('id', $request->tags)->with('createdBy')->get();
            $requestData['tags'] = $tags;
        }

        return json_encode($requestData);
    }

    private function dateFormat($date)
    {
        return Carbon::createFromFormat('d/m/Y', $date)->format('Y/m/d');
    }

    private function dateTimeFormat($date)
    {
        return Carbon::createFromFormat('g:i A', $date)->format('H:i:s');
    }
}
