<?php

namespace App\Http\Resources;

use App\Category;
use App\Collection;
use App\Enums\CampaignType;
use App\Enums\ClassPublishStatus;
use App\Enums\CollectionType;
use App\Enums\DiscountType;
use App\Enums\LiveClassStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublishStatus;
use App\Enums\Recurrence;
use App\Enums\ReviewStatus;
use App\Enums\VendorRoleType;
use App\Helpers\CollectionHelper;
use App\Helpers\SlugHelper;
use App\Helpers\UserHelper;
use App\Lib\Util;
use App\Model\Partner\Discount;
use App\Model\Partner\Fee;
use App\Model\Partner\PartnerLiveClassSchedule;
use App\Model\Partner\Service;
use App\Model\Partner\StudentRegistration;
use App\Model\Student;
use App\Order;
use App\Tag;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as CollectionInstance;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MobileData extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $datas = [
            'id'                   => $this->id,
            'title'                => Str::title($this->title),
            'collection_type'      => $this->collection_type,
            //'collection_type_name' => $this->collection_type ? ucwords(preg_replace('/([a-z])([A-Z])/', '\\1 \\2', (CollectionType::getKey($this->collection_type)))) : null,
            'collection_type_name' => Util::collectionTypeLookup($this->collection_type),
            'is_featured'          => $this->is_featured ? true : false,
            'is_recommended'       => $this->is_recommended ? true : false,
            'slug'                 => $this->slug,
            'head_category'        => '',
            'featured_image'       => '',
            'status'               => $this->status,
        ];

        if ($this->collection_type == CollectionType::campaignsType) {
            if (isset($this->ischeck) && $this->ischeck) {
                $published_content = $this->published_content;
            } else {
                $published_content = json_decode($this->published_content);
            }

            $datas = $this->addMedia($datas, $published_content);
            $datas['description'] = $published_content->excerpt;

            return $datas;
        }

        if (! is_int($this)) {
            $this->load('product.coupons', 'product.productReviews');
        }
        if (isset($this->product) && $this->product) {
            $datas['product'] = $this->product;
            $datas['product_review'] = $this->product->productReviews;
            $datas['coupons'] = $this->product->coupons;
            $this->product->unsetRelation('productReviews')->unsetRelation('coupons');
        }
        $datas['rating_percentage'] = null;

        $productPrice = CollectionHelper::getActivePackage($this);
        $original_product_price = 0;
        $product_price_id = '';
        $discount = null;
        if ($productPrice) {
            $original_product_price = $productPrice->price;
            $product_price_id = $productPrice->id;
            if ($productPrice->discounts and $productPrice->discounts->status == PublishStatus::Published) {
                $discount = $productPrice->discounts;
            }
        }

        $datas['original_product_price'] = $original_product_price;
        $datas['product_price_id'] = $product_price_id;

        $datas['product_price'] = 0;

        $datas['discount_amount'] = 0;
        $data['trainee_partner'] = false;
        $datas['additional_threshold'] = null;
        $datas['additional_amount'] = null;
        $datas['isPercentage'] = null;

        $datas['vendor_id'] = 0;
        $datas['vendor_class_id'] = 0;

        if ($this->vendor_id) {
            $datas['vendor_id'] = $this->vendor_id;
        }

        if ($this->vendor_class_id) {
            $datas['vendor_class_id'] = $this->vendor_class_id;
        }

        if (! $this->isdetails) {
            $datas = $this->bindBoughtData($datas, $this->bought_data);
            $datas = $this->bindSessionData($datas, $this->session_data);
        }

        if ((isset($this->product) && isset($this->product->prices) && count($this->product->prices) > 0 && $this->product->prices[0]->discounts)) {
            $discount = $this->product->prices[0]->discounts;
            if (Carbon::parse($discount->start_date)->isPast() && ! Carbon::parse($discount->end_date)->endOfDay()->isPast()) {
                $datas['discount_amount'] = $discount ? $discount->amount : 0;
                $datas['additional_threshold'] = $discount ? $discount->additional_threshold : null;
                $datas['additional_amount'] = $discount ? $discount->additional_amount : null;
                $datas['isPercentage'] = $discount ? $discount->is_percentage : null;
            }
        }

        if ($discount) {
            // $discount = $this->product->prices[0]->discounts;
            if (Carbon::parse($discount->start_date)->isPast() && ! Carbon::parse($discount->end_date)->endOfDay()->isPast()) {
                $datas['discount_amount'] = $discount ? $discount->amount : 0;
                $datas['additional_threshold'] = $discount ? $discount->additional_threshold : null;
                $datas['additional_amount'] = $discount ? $discount->additional_amount : null;
                $datas['isPercentage'] = $discount ? $discount->is_percentage : null;
            }
        }

        if (isset($this->product) && $this->product && isset($this->product->productReview) && count($this->product->productReview) > 0) {
            $count = 0;
            $approvedCount = 0;
            $allreviews = $this->product->productReview;
            foreach ($allreviews as $review) {
                if (isset($review->rating) && $review->review_status == ReviewStatus::Approved) {
                    $count += $review->rating;
                    $approvedCount = $approvedCount + 5;
                }
            }
            $datas['rating_percentage'] = round(($count / $approvedCount) * 100, 2);
        }

        if ($this->published_content) {
            if (isset($this->ischeck) && $this->ischeck) {
                $published_content = $this->published_content;
            } else {
                $published_content = json_decode($this->published_content);
            }

            if ($this->collection_type == CollectionType::campaignsType) {
                $datas['description'] = $published_content->excerpt;
            }

            $datas = $this->addMedia($datas, $published_content);



            $datas = $this->addServices($datas, $published_content); // add services

            if (isset($published_content->location) && $published_content->location) {
                $datas['location'] = Str::title($published_content->location);
            } else {
                $datas['location'] = '';
            }

            if (isset($published_content->categories) && $published_content->categories && count($published_content->categories) > 0) {
                // $datas['categories'] =  $published_content->categories;
                $datas['head_category'] = isset($published_content->categories[0]->name) ? Str::title($published_content->categories[0]->name) : '';
            }
            if ($this->collection_type == CollectionType::testimonials) {
                $datas['author_company'] = isset($published_content->author_company) ? $published_content->author_company : null;
                $datas['author_designation'] = isset($published_content->author_designation) ? $published_content->author_designation : null;
            } elseif ($this->collection_type == CollectionType::panelList) {
                $datas['image_gallery'] = (isset($published_content->images) && $published_content->images) ? $published_content->images : [];
            } elseif ($this->collection_type == CollectionType::videos) {
                $datas['featured_video'] = isset($published_content->featured_video) ? $published_content->featured_video : null;
                $datas['read_time'] = (isset($published_content->read_time) && $published_content->read_time) ? $published_content->read_time : null;
                $datas['collection_type_name'] = 'Video';
            } elseif ($this->collection_type == CollectionType::galleries) {
                $datas['url'] = isset($published_content->images) ? $published_content->images : null;
            } elseif ($this->collection_type == CollectionType::blogs) {
                $datas['created_at'] = $this->created_at;
                if (isset($published_content->author_designation)) {
                    $datas['check'] = null;
                    $datas['author_designation'] = $published_content->author_designation;
                }
                if (isset($published_content->author_description) && $published_content->author_description) {
                    $datas['author_description'] = $published_content->author_description;
                }
                if (isset($published_content->categories) && $published_content->categories) {
                    $datas['categories'] = $published_content->categories;
                }
                $datas['featured_videos'] = (isset($published_content->featured_videos) && $published_content->featured_videos) ? $published_content->featured_videos : [];
            } elseif ($this->collection_type == CollectionType::careers) {
                $published_content = json_decode($this->published_content);
                $datas['department'] = (isset($published_content->author_designation) && $published_content->author_designation) ? $published_content->author_designation : null;
                $datas['location'] = (isset($published_content->location) && $published_content->location) ? $published_content->location : null;
                $datas['fee'] = (isset($published_content->fee) && $published_content->fee) ? $published_content->fee : null;
                $datas['location'] = (isset($published_content->location) && $published_content->location) ? $published_content->location : null;
                $datas['experience'] = (isset($published_content->experience) && $published_content->experience) ? $published_content->experience : null;
                $datas['qualification'] = (isset($published_content->qualification) && $published_content->qualification) ? $published_content->qualification : null;
            } elseif ($this->collection_type == CollectionType::startups) {
                $datas['sub_title'] = (isset($published_content->sub_title) && $published_content->sub_title) ? $published_content->sub_title : null;
                // $datas['solutions'] = (isset($published_content->solutions) && $published_content->solutions) ? $published_content->solutions : null;
                $datas['founders'] = (isset($published_content->founders) && $published_content->founders && count($published_content->founders) > 0) ? $published_content->founders : null;
            } elseif ($this->collection_type == CollectionType::articles) {
                $datas['organisation_name'] = (isset($published_content->organisation_name) && $published_content->organisation_name) ? $published_content->organisation_name : null;
                $datas['collection_type_name'] = 'Article';
            } elseif ($this->collection_type == CollectionType::partnerGalleries) {
                $datas['featured_image'] = isset($published_content->images) && count($published_content->images) > 0 ? $published_content->images[0] : [];
                $datas['images'] = isset($published_content->images) && count($published_content->images) ? $published_content->images : [];
            } elseif ($this->collection_type == CollectionType::people) {
                $datas['featured_image'] = $published_content->featured_image && $published_content->featured_image->url ? $published_content->featured_image : '';
            } elseif ($this->collection_type == CollectionType::awards) {
                $datas['image_gallery'] = (isset($published_content->images) && $published_content->images) ? $published_content->images : [];
            } elseif ($this->collection_type == CollectionType::events || $this->collection_type == CollectionType::workshops || $this->collection_type == CollectionType::classes || $this->collection_type == CollectionType::classDeck) {
                if ($this->collection_type == CollectionType::workshops) {
                    $datas['collection_type_name'] = 'Workshop';
                } elseif ($this->collection_type == CollectionType::classes) {
                    $datas['collection_type_name'] = 'Class';
                } elseif ($this->collection_type == CollectionType::classDeck) {
                    $description = '';
                    if ($published_content and isset($published_content->description) and $published_content->description) {
                        $description = $published_content->description;
                    }
                    $datas['collection_type_name'] = 'Live Class';
                    $datas['description'] = $description;
                } else {
                    $datas['collection_type_name'] = 'Event';
                }
                $datas['start_date'] = (isset($published_content->start_date) && $published_content->start_date) ? $published_content->start_date : null;
                $datas['end_date'] = (isset($published_content->end_date) && $published_content->end_date) ? $published_content->end_date : null;
                $datas['start_time'] = (isset($published_content->start_time) && $published_content->start_time) ? $published_content->start_time : null;
                $datas['end_time'] = (isset($published_content->end_time) && $published_content->end_time) ? $published_content->end_time : null;
                $datas['month_year'] = (isset($published_content->start_date) && $published_content->start_date) ? Carbon::parse($published_content->start_date)->format('M Y') : null;
                $datas['duration'] = (isset($published_content->duration) && $published_content->duration) ? $published_content->duration.' Minutes' : '';
                $datas['frequency'] = (isset($published_content->frequency) && $published_content->frequency) ? $published_content->frequency : '';

                if (isset($this->product) && $this->product) {
                    $datas['product'] = $this->product;
                    $this->product->unsetRelation('productReviews');
                    $datas['stock'] = $this->product->stock;
                }


                unset($datas['product']);

                $datas['start_date'] = (isset($published_content->start_date) && $published_content->start_date) ? $published_content->start_date : null;
                $datas['start_time'] = (isset($published_content->start_time) && $published_content->start_time) ? $published_content->start_time : null;
                $datas['collection_type_name'] = 'Activities';
            } elseif ($this->collection_type == CollectionType::news) {
                $datas['source_name'] = (isset($published_content->source_name) && $published_content->source_name) ? $published_content->source_name : null;
            }

            $datas['rating_percentage'] = null;
            $datas['rating_count'] = 0;
            $datas['rating_percentage_value'] = null;

            $publishedReviews = new CollectionInstance();

            if (isset($this->productReviews) && count($this->productReviews) > 0) {
                $collection = collect($this->productReviews);

                $collection->map(function ($item, $key) use ($publishedReviews) {
                    if ($item->review_status == ReviewStatus::Approved) {
                        $publishedReviews->push($item);
                    }
                });
            }

            if (isset($publishedReviews) && count($publishedReviews) > 0) {
                $count = 0;
                $allreviews = $publishedReviews;
                foreach ($allreviews as $review) {
                    if (isset($review->rating)) {
                        $count += $review->rating;
                    }
                }
                $datas['rating_count'] = count($allreviews);
                $datas['rating_percentage'] = round(($count / (count($allreviews) * 5)) * 100, 2);
                $datas['rating_percentage_value'] = (string) round($count / count($allreviews), 2);
            }

            if ($this->isdetails) {
                if ($this->collection_type == CollectionType::events || $this->collection_type == CollectionType::workshops || $this->collection_type == CollectionType::classes || $this->collection_type == CollectionType::classDeck) {
                    $datas['trainer_name'] = (isset($published_content->author_name) && $published_content->author_name) ? $published_content->author_name : '';
                    if (isset($published_content->author_designation) && $published_content->author_designation) {
                        $datas['trainer_designation'] = $published_content->author_designation;
                    }
                    if (isset($published_content->author_description) && $published_content->author_description) {
                        $datas['trainer_description'] = $published_content->author_description;
                    }
                    if (isset($published_content->author_experience) && $published_content->author_experience) {
                        $datas['trainer_experience'] = $published_content->author_experience;
                    }
                    $datas['trainer_image'] =
                        (isset($published_content->author_image) && $published_content->author_image && $published_content->author_image->url) ? Storage::url($published_content->author_image->url) : '';

                    $datas['highlights'] = (isset($published_content->highlights) && $published_content->highlights) ? $published_content->highlights : null;
                    $datas['content'] = (isset($published_content->content) && $published_content->content) ? $published_content->content : null;
                    $datas['excerpt'] = (isset($published_content->excerpt) && $published_content->excerpt) ? $published_content->excerpt : null;
                    $datas['source'] = (isset($published_content->source) && $published_content->source) ? $published_content->source : null;
                    $datas['date'] = (isset($published_content->date) && $published_content->date) ? $published_content->date : null;
                    $datas['location'] = (isset($published_content->location) && $published_content->location) ? $published_content->location : null;
                    $datas['state'] = (isset($published_content->state) && $published_content->state) ? $published_content->state : null;
                    $datas['country'] = (isset($published_content->country) && $published_content->country && $published_content->country->name) ? $published_content->country->name : '';

                    // $datas['rating_percentage'] = null;
                    $discount = $discount;
                    $price = $productPrice;

                    if ($discount) {
                        if ($discount and Carbon::parse($discount->start_date)->isPast() && ! Carbon::parse($discount->end_date)->endOfDay()->isPast()) {
                        } else {
                            $discount = null;
                        }
                    }
                    $datas['product_price_id'] = $price ? $price->id : '';
                    $datas['discount_amount'] = $discount ? $discount->amount : '';
                    $datas['additional_threshold'] = $discount ? $discount->additional_threshold : '';
                    $datas['additional_amount'] = $discount ? $discount->additional_amount : '';
                    $datas['isPercentage'] = $discount ? $discount->is_percentage : false;
                    $datas['discount_code'] = $discount ? $discount->code : '';
                    $datas['discount_start_date'] = $discount ? $discount->start_date : '';
                    $datas['discount_end_date'] = $discount ? $discount->end_date : '';
                    $datas['discount_status'] = $discount ? (string) $discount->status : '';

                    $datas['product_id'] = isset($this->product) && $this->product ? $this->product->id : '';
                }
            }
        }



        if (isset($datas['original_product_price']) and $datas['original_product_price']) {
            if ($this->published_price) {
                $datas['product_price'] = $this->published_price;
            } else {
                $datas['product_price'] = $datas['original_product_price'];
            }
        }

        if (isset($datas['product_price']) and $datas['product_price']) {
            $datas['is_free'] = false;
        } else {
            $datas['is_free'] = true;
        }

        if (isset($datas['start_date']) and $datas['start_date']) {
            $startDate = Carbon::createFromFormat('Y/m/d', $datas['start_date']);
            $datas['start_date'] = $startDate->format('D, d M  Y');
            $datas['start_date_time'] = $startDate;
        }

        if (isset($datas['end_date']) and $datas['end_date']) {
            $endDate = Carbon::createFromFormat('Y/m/d', $datas['end_date']);
            $datas['end_date'] = $endDate->format('D, d M  Y');
            $datas['end_date_time'] = $endDate;
        }

        if ($this->partner and ! $this->isdetails) {
            return $this->mobileData($datas);
        }

        if ($this->partner and $this->isdetails) {
            return $this->detailsDataData($datas, $this->edit);
        }

        if (isset($datas['start_time']) and $datas['start_time']) {
            $start_time = $this->dateTimeFormat($datas['start_time']);
            $datas['start_time'] = $start_time;
        }

        if (isset($datas['end_time']) and $datas['end_time']) {
            $end_time = $this->dateTimeFormat($datas['end_time']);
            $datas['end_time'] = $end_time;
            $datas['end_time_parse'] = $this->dateTimeFormat($datas['end_time'], false);
        }

        if ($this->collection_type == CollectionType::campaigns) {
            return $this->campaign($datas, $published_content);
        }

        if ($this->collection_type == CollectionType::caseStudy) {
            return $this->caseStudy($datas);
        }


        if (in_array($this->collection_type, [CollectionType::classDeck, CollectionType::classes]) and $this->isdetails) {
            $datas =  $this->classDataFun($datas);
        }

        $datas = $this->checkDateStatus($datas, $published_content);

        return $datas;
    }

    private function caseStudy($data)
    {
        $webUrl = config('app.client_url').'/young_xpert/'.$data['slug'];
        $data['web_url'] =  $webUrl;

        return $data;
    }

    private function mobileData($data)
    {
        $location = '';
        if (isset($data['location']) && $data['location']) {
            $location = Str::title($data['location']);
        }

        $start_date = '';
        $start_time = '';
        $start_date_month = '';
        $product_price = 'Free';
        $isFree = true;
        $status = 1;
        $isExpired = false;

        if ($data['status'] != PublishStatus::Published) {
            $status = 0;
        }

        if ($status) {
            $nowEndDate = now()->startOfDay();
            if (isset($data['end_date_time']) and $data['end_date_time']) {
                $endDate = $data['end_date_time'];
                $coll_end_date = $endDate->startOfDay();
                if ($coll_end_date < $nowEndDate) {
                    $isExpired = true;
                    $status = 0;
                }
            } else {
                $status = 0;
            }
        }

        if (isset($data['start_date_time']) and $data['start_date_time']) {
            $startDate = $data['start_date_time'];
            $start_date = $startDate->format('d');
            $start_date_month = $startDate->format('M');
        }

        if (isset($data['start_time']) and $data['start_time']) {
            $start_time = $this->dateTimeFormat($data['start_time']);
        }

        if (isset($data['product_price']) and $data['product_price']) {
            $product_price = '₹'.number_format($data['product_price'], 2);
            $isFree = false;
        }

        $datas = [
            'id'              => $data['id'],
            'title'           => Str::title($data['title']),
            'slug'            => $data['slug'],
            'is_featured'     => $data['is_featured'] ? true : false,
            'is_recommended'  => $data['is_recommended'] ? true : false,
            'location'        => $location,
            'start_date'      => $start_date,
            'start_time'      => $start_time,
            'start_date_month'=> $start_date_month,
            'product_price'   => $product_price,
            'is_free'         => $isFree,
            'status'          => $status,
            'is_expired'      => $isExpired,
        ];

        return $datas;
    }

    public function detailsDataData($data, $isEdit)
    {
        // return $data;
        $excerpt = '';
        $highlights = '';
        $description = '';
        $start_date = '';
        $end_date = '';
        $start_time = '';
        $end_time = '';
        $discount = '';
        $discount_code = '';
        $discount_start_date = '';
        $discount_end_date = '';
        $discount_status = '';
        $additional_threshold = '';
        $additional_amount = '';
        $price = '';
        $is_percenatge = false;
        $trainer_name = '';
        $trainer_image = '';
        $trainer_designation = '';
        $trainer_experience = '';
        $trainer_description = '';
        $unit = '';
        $event_image = '';
        $categories = [];
        $tags = [];
        $location = '';

        if (isset($data['location']) && $data['location']) {
            $location = $isEdit ? $data['location'] : Str::title($data['location']);
        }

        if (isset($data['excerpt']) and $data['excerpt']) {
            $excerpt = $data['excerpt'];
        }

        if (isset($data['highlights']) and $data['highlights']) {
            $highlights = $data['highlights'];
        }

        if (isset($data['content']) and $data['content']) {
            $description = $data['content'];
        }

        if (isset($data['start_date']) and $data['start_date']) {
            $start_date = $isEdit ? $data['start_date_time'] : $data['start_date_time']->format('M d, Y');
        }

        if (isset($data['end_date']) and $data['end_date']) {
            $end_date = $isEdit ? $data['end_date_time'] : $data['end_date_time']->format('M d, Y');
        }

        if (isset($data['start_time']) and $data['start_time']) {
            $start_time = $this->dateTimeFormat($data['start_time']);
        }

        if (isset($data['end_time']) and $data['end_time']) {
            $end_time = $this->dateTimeFormat($data['end_time']);
        }

        if (isset($data['discount_amount']) and $data['discount_amount']) {
            $discount = $isEdit ? $data['discount_amount'] : '₹'.number_format($data['discount_amount'], 2);
        }

        if (isset($data['discount_code']) and $data['discount_code']) {
            $discount_code = $data['discount_code'];
        }

        if (isset($data['discount_start_date']) and $data['discount_start_date']) {
            $parseDate = Carbon::parse($data['discount_start_date']);
            $discount_start_date = $isEdit ? $parseDate : $parseDate->format('M d, Y');
        }

        if (isset($data['discount_end_date']) and $data['discount_end_date']) {
            $parseDate = Carbon::parse($data['discount_end_date']);
            $discount_end_date = $isEdit ? $parseDate : $parseDate->format('M d, Y');
        }

        if (isset($data['additional_threshold']) and $data['additional_threshold']) {
            $additional_threshold = $isEdit ? $data['additional_threshold'] : '₹'.number_format($data['additional_threshold'], 2);
        }

        if (isset($data['additional_amount']) and $data['additional_amount']) {
            $additional_amount = $isEdit ? (string) $data['additional_amount'] : '₹'.number_format($data['additional_amount'], 2);
        }

        if (isset($data['product_price']) and $data['product_price']) {
            $price = $isEdit ? (string) $data['product_price'] : '₹'.number_format($data['product_price'], 2);

            if ($isEdit) {
                $data['product_price'] = (string) $data['original_product_price'];
                $price = (string) $data['original_product_price'];
            }
        }

        if (isset($data['isPercentage']) and $data['isPercentage']) {
            $is_percenatge = true;
            if (! $isEdit) {
                $product_price = $data['product_price'];
                $discount_amount = $data['discount_amount'];
                $discount = '₹'.number_format(($product_price * $discount_amount) / 100, 2);
            }
        }

        if (isset($data['trainer_image']) and $data['trainer_image']) {
            $trainer_image = $data['trainer_image'];
        }

        if (isset($data['trainer_designation']) and $data['trainer_designation']) {
            $trainer_designation = $isEdit ? $data['trainer_designation'] : Str::title($data['trainer_designation']);
        }

        if (isset($data['trainer_experience']) and $data['trainer_experience']) {
            $trainer_experience = (string) $data['trainer_experience'];
        }

        if (isset($data['trainer_name']) and $data['trainer_name']) {
            $trainer_name = $isEdit ? (string) $data['trainer_name'] : Str::title($data['trainer_name']);
        }

        if (isset($data['trainer_description']) and $data['trainer_description']) {
            $trainer_description = (string) $data['trainer_description'];
        }

        if (isset($data['discount_status']) and $data['discount_status']) {
            $discount_status = (string) $data['discount_status'];
        }

        // $trainer_name
        if (isset($data['stock']) and $data['stock']) {
            $unit = (string) $data['stock'];
        }

        if (isset($data['featured_image']) and $data['featured_image']) {
            $event_image = $data['featured_image'];
        }

        if ($this->categories) {
            $categoriesIds = [];
            $decodedCatIds = json_decode($this->categories);
            for ($i = 0; $i < count($decodedCatIds); $i++) {
                $categoriesIds[] = (string) $decodedCatIds[$i];
            }
            $categories = $isEdit ? $categoriesIds : $this->getCategoriesNames($categoriesIds);
        }

        if ($this->tags) {
            $tagsIds = [];
            $decodedTagIds = json_decode($this->tags);
            for ($i = 0; $i < count($decodedTagIds); $i++) {
                $tagsIds[] = (string) $decodedTagIds[$i];
            }
            $tags = $isEdit ? $tagsIds : $this->getTagsNames($tagsIds);
        }

        $data = [
            'id'                       => $this->id,
            'title'                    => $this->title,
            'location'                 => $location,
            'excerpt'                  => $excerpt,
            'highlights'               => $highlights,
            'description'              => $description,
            'start_date'               => $start_date,
            'end_date'                 => $end_date,
            'start_time'               => $start_time,
            'end_time'                 => $end_time,
            'discount'                 => $discount,
            'discount_code'            => $discount_code,
            'discount_start_date'      => $discount_start_date,
            'discount_end_date'        => $discount_end_date,
            'additional_threshold'     => $additional_threshold,
            'additional_amount'        => $additional_amount,
            'discount_status'          => $discount_status,
            'price'                    => $price,
            'is_percenatge'            => $is_percenatge,

            'trainer_image'            => $trainer_image,
            'trainer_name'             => $trainer_name,
            'trainer_designation'      => $trainer_designation,
            'trainer_experience'       => $trainer_experience,
            'trainer_description'      => $trainer_description,
            // 'trainer_about'            => $trainer_about,
            'categories'               => $categories,
            'tags'                     => $tags,
            'unit'                     => $unit,
            'image'                    => $event_image,
        ];

        return $data;
    }

    private function getCategoriesNames($ids)
    {
        // return  Category::whereIn('id', $ids)->pluck('name')->get();

        $categories = [];
        foreach (Category::whereIn('id', $ids)->pluck('name') as $name) {
            $categories[] = Str::title($name);
        }

        return $categories;
    }

    private function getTagsNames($ids)
    {
        // return Tag::whereIn('id', $ids)->pluck('name');

        $tags = [];
        foreach (Tag::whereIn('id', $ids)->pluck('name') as $name) {
            $tags[] = Str::title($name);
        }

        return $tags;
    }

    private function campaign($data, $published_content)
    {
        $type = '';
        $type_id = '';
        $excerpt = '';
        $isDetails = false;
        $term_conditions = '';
        $highlights = '';
        $start_date = '';
        $end_date = '';
        $instructions = '';
        $images = [];
        $categoryDatas = [];
        $sponsorDatas = [];

        if ($this->isdetails) {
            $isDetails = true;
        }
        if (isset($published_content->campaign_type) and $published_content->campaign_type) {
            $type = $published_content->campaign_type->name;
            $type_id = $published_content->campaign_type->id;
        }

        if (isset($published_content->excerpt) and $published_content->excerpt) {
            $excerpt = $published_content->excerpt;
        }

        if (isset($published_content->images) and $published_content->images and count($published_content->images)) {
            foreach ($published_content->images as $image) {
                $images[] = $image->full_url;
            }
        }

        if (isset($published_content->start_date) and $published_content->start_date) {
            $startDate = Carbon::createFromFormat('Y/m/d', $published_content->start_date);
            $start_date = $startDate->format('M d, Y');
            // $datas['start_date_time'] = $startDate;
        }

        if (isset($published_content->end_date) and $published_content->end_date) {
            $endDate = Carbon::createFromFormat('Y/m/d', $published_content->end_date);
            $end_date = $endDate->format('M d, Y');
            // $datas['start_date_time'] = $startDate;
        }

        $exist_terms = false;
        if (isset($published_content->terms_conditions) and $published_content->terms_conditions) {
            $exist_terms = true;
        }

        $meta = null;

        if ($isDetails) {

            $meta = (isset($published_content->meta) && $published_content->meta) ? $published_content->meta : null;;

            if (isset($published_content->terms_conditions) and $published_content->terms_conditions) {
                $term_conditions = $published_content->terms_conditions;
            }

            // This function os used to replace the terms of any campaign with the open campaign terms.
            if (isset($this->get_terms) && $this->get_terms) {
                $openCampaign = Collection::where('collection_type', CollectionType::campaigns)
                    ->whereNotNull('published_content')
                    ->where('status', PublishStatus::Published)
                    ->where('published_content->campaign_type->id', CampaignType::Open)
                    ->where('published_content->terms_conditions', '!=', '')
                    ->latest()
                    ->first();
                if ($openCampaign && $openCampaign->id != $this->id) {
                    $openPublished = json_decode($openCampaign->published_content);
                    if (isset($openPublished->terms_conditions) and $openPublished->terms_conditions) {
                        $term_conditions = $openPublished->terms_conditions;
                    }
                }
            }

            if (isset($published_content->content) and $published_content->content) {
                $highlights = $published_content->content;
            }

            if (isset($published_content->instructions) and $published_content->instructions) {
                $instructions = $published_content->instructions;
            }
        }


        if (isset($published_content->sponsors) and $published_content->sponsors and count($published_content->sponsors)) {
            $sponsorsDatas = $published_content->sponsors;
            $sponsorIds = [];
            if(count($sponsorsDatas)){
                info(count($sponsorsDatas));
                
                foreach($sponsorsDatas as $sponsorData){ 
                    $sponsorIds[]= $sponsorData->id;
                }
                
            }
           

            
            $sponsors = Collection::whereIn('id', $sponsorIds)->with('medias')->where('collection_type', CollectionType::sponsers)->get();

            foreach ($sponsors as $sponsor) {
                $sponsorImages = [];
                if (isset($sponsor->medias) and count($sponsor->medias)) {
                    $sponsorImages = $this->collectionImages($sponsor->medias);
                }

                // $published_content = json_decode($sponsor['published_content']);
                $sponser_excerpt = null;
                if (isset($sponsor->excerpt) and $sponsor->excerpt) {
                    $sponser_excerpt = $sponsor->excerpt;
                }


                $sponsorDatas[] = [
                    'id'            => $sponsor->id,
                    'title'         => Str::title($sponsor->title),
                    'slug'          => $this->slug($sponsor->title),
                    'excerpt'       => $sponser_excerpt,
                    'images'        => $sponsorImages,
                    'status'        => $sponsor->status
                    
                ];
                
                
            }
            foreach($sponsorDatas as $key=>$sponsorData){
                $sponsor = Collection::where('collection_type', CollectionType::sponsers)
                                       ->where('id', $sponsorData['id'] )
                                       ->first();
                if($sponsor->status != PublishStatus::Published){                       
                    Arr::forget($sponsorDatas,$key);
                }
            }
            
        }
       
        
        
        $categoryDatas = $this->getCategoriesData($this);

        $webUrl = config('app.client_url')."/mobile/campaign/".$data['slug'];
        // $webUrl = config('client.short_friend_url');
/* 
        if($this->id==config('app.colorthon_campaign_type.creative_streak')){
            $dynamicUrl = config('app.colorthon_campaign_link.creative_streak');
        }elseif($this->id==config('app.colorthon_campaign_type.all_about_shades')){
            $dynamicUrl = config('app.colorthon_campaign_link.all_about_shades');
        }elseif($this->id==config('app.colorthon_campaign_type.art_treat')){
            $dynamicUrl = config('app.colorthon_campaign_link.art_treat');
        }else{
            $dynamicUrl = "";
        } */

        $dynamicUrl = $this->getDynamicUrl() ? $this->getDynamicUrl() : "";

        return [
            'id'              => $this->id,
            'type'            => $type,
            'type_id'         => $type_id,
            'title'           => $data['title'],
            'images'          => $images,
            'excerpt'         => $excerpt,
            'exist_terms'     => $exist_terms,
            'term_conditions' => $this->when($isDetails, $term_conditions),
            'highlights'      => $this->when($isDetails, $highlights),
            'instructions'    => $this->when($isDetails, $instructions),
            'start_date'      => $start_date,
            'end_date'        => $end_date,
            'slug'            => $data['slug'],

            'sponsors'        => $sponsorDatas,
            'categories'      => $categoryDatas,
            'meta'            => $meta,
            'web_url'         => $webUrl,
            'dynamic_url'     => $dynamicUrl,
            'submit_count'    => "3",
            'previous_entry_submission_status' => $this->previous_entry_submission_status,
            'submited_count'      => $this->submited_count,
        ];
    }

    private function getCategoriesData($data)
    {
        $categoryDatas = [];

        if (isset($data->categories) and $data->categories and ! is_null($data->categories)) {
            $categoriesDatas = json_decode($data->categories);
            $categoriesDatas = is_array($categoriesDatas) ? $categoriesDatas : json_decode($categoriesDatas);
            $categoriesIds = [];
            if (count($categoriesDatas)) {
                $categoriesIds = $categoriesDatas;
            }

            $categories = Collection::whereIn('id', $categoriesIds)->with('medias')->where('collection_type', CollectionType::campaignCategories)->get();
            foreach ($categories as $category) {
                $categoryImages = [];
                $max_size = 0.0;
                $published_content = json_decode($category->published_content);
                if (isset($category->medias) and count($category->medias)) {
                    $categoryImages = $this->collectionImages($category->medias);
                }

                if (isset($published_content->max_size) and $published_content->max_size) {
                    $max_size = (int) $published_content->max_size;
                }

                $cat_excerpt = null;
                if (isset($published_content->excerpt) and $published_content->excerpt) {
                    $cat_excerpt = $published_content->excerpt;
                }

                $subCategoriesAndTypes = $this->subCategoriesAndTypes($category);

                $categoryDatas[] = [
                    'id'              => $category->id,
                    'title'           => Str::title($category->title),
                    'slug'            => $this->slug($category->title),
                    'images'          => $categoryImages,
                    'excerpt'         => $cat_excerpt,
                    'sub_categories'  => $subCategoriesAndTypes['subCategories'],
                    'doc_types'       => $subCategoriesAndTypes['docTypes'],
                    'max_size'        => $max_size,
                ];
            }
            foreach($categoryDatas as $key=>$categoryData){
                $category = Collection::where('collection_type', CollectionType::campaignCategories)
                                       ->where('id', $categoryData['id'] )
                                       ->first();
                if($category->status != PublishStatus::Published){                       
                    Arr::forget($categoryDatas,$key);
                }
            }
        }

        return $categoryDatas;
    }

    private function subCategoriesAndTypes($data)
    {
        $datas = [];
        $docTypes = [];
        $published_content = json_decode($data->published_content);

        if (isset($data->categories) and $data->categories and ! is_null($data->categories)) {
            $subCategoriesIds = [];
            $subCategoriesDatas = json_decode($data->categories);
            $subCategoriesDatas = is_array($subCategoriesDatas) ? $subCategoriesDatas : json_decode($subCategoriesDatas);
            if (count($subCategoriesDatas)) {
                $subCategoriesIds = $subCategoriesDatas;
            }

            $sub_categories = Collection::whereIn('id', $subCategoriesIds)->with('medias')
                ->where('collection_type', CollectionType::campaignSubCategories)->get();

            foreach ($sub_categories as $sub_category) {
                $subcategoryImages = [];
                if (isset($sub_category->medias) and count($sub_category->medias)) {
                    $subcategoryImages = $this->collectionImages($sub_category->medias);
                }
                $pub_content = json_decode($sub_category->published_content);

                $sub_excerpt = null;
                if (isset($pub_content->excerpt) and $pub_content->excerpt) {
                    $sub_excerpt = $pub_content->excerpt;
                }

                $datas[] = [
                    'id'        => $sub_category->id,
                    'title'     => Str::title($sub_category->title),
                    'slug'      => $this->slug($sub_category->title),
                    'images'    => $subcategoryImages,
                    'excerpt'   => $sub_excerpt,
                ];
            }
            foreach($datas as $key=>$data){
                $subCategory = Collection::where('collection_type', CollectionType::campaignSubCategories)
                                       ->where('id', $data['id'] )
                                       ->first();
                if($subCategory->status != PublishStatus::Published){                       
                    Arr::forget($datas,$key);
                }
            }

        }

        if (isset($published_content) and $published_content->category_type and count($published_content->category_type)) {
            $docTypes = $this->docTypes($published_content->category_type);
        }

        return ['subCategories' => $datas, 'docTypes' => $docTypes];
    }

    private function collectionImages($images)
    {
        $datas = [];
        foreach ($images as $image) {
            if (isset($image->url)) {
                $datas[] = Storage::disk('s3')->url($image->url);
            }
        }

        return $datas;
    }

    private function slug($title)
    {
        $slugHelper = new SlugHelper();

        return $slugHelper->slugify($title);
    }

    private function docTypes($types)
    {
        $datas = [];
        if (isset($types) and count($types)) {
            foreach ($types as $type) {
                $datas[] = [
                    'id'   => $type->id,
                    'name' => $type->name,
                ];
            }
        }

        return $datas;
    }

    private function classDataFun($data)
    {
        $liveClass = DB::connection('partner_mysql')->table('live_classes')->where('vendor_class_id', $this->vendor_class_id)->first();
        $vendorClasses = DB::connection('partner_mysql')->table('vendor_classes')->where('id', $this->vendor_class_id)->first();

        $user  = auth()->user();
        $data['live_class'] = false;
        $data['live_class_id'] = 0;

        $data['allow_other'] = false;
        $data['is_bought'] = false;
        $data['is_payment_done'] = false;
        $isAllow = false;
        $payment = null;
        if ($user) {

            if ($user->role_id == VendorRoleType::Student || $user->role_id == VendorRoleType::Guardian) {
                $isAllow = true;
            }
            $userIds = UserHelper::getStudentsIds();
            $ordersData = Order::whereIn('purchaser_id', $userIds)
            ->where('collection_id', $this->id)
            ->where('payment_status', PaymentStatus::Received);


            $order = $ordersData->latest()->first();

            $studentIds = Student::whereIn('user_id' , $userIds)->pluck('id');
            $registeredDBKids = $vendorClasses ? StudentRegistration::whereIn('student_id' , $studentIds)->where('vendorclass_id', $vendorClasses->id)->count() : 0;

            if ($registeredDBKids) {
                $data['is_bought'] = true;
                if (count($studentIds) > $registeredDBKids) {
                    $data['allow_other'] = true;
                }
            }

            if ($order) {
                $payment = [
                    'date'      => $order->created_at->format('d/m/Y'),
                    'amount'    => "₹".$order->amount,
                    "order_id"  => $order->code,
                    "currency"  => "₹",
                    "id"        => $order->id
                ];
                $data['is_payment_done'] = true;
            }

        }

        $data['recurrence'] = "";
        $data['frequency'] =  $vendorClasses ? (string)$vendorClasses->frequencey_per_month : "";
        $data['session_id'] = 0;
        $data['internal_id'] = "";
        $data['meeting_id'] = "";
        $data['disable_user_recording'] = false;
        if ($liveClass) {
            $data['live_class'] = true;
            $data['disable_user_recording'] = $liveClass->disable_user_recording ? true : false;
            $data['live_class_id'] = $liveClass->id;
            $data['recurrence'] = Recurrence::getKey($liveClass->recurrence);
            $allStatus = [LiveClassStatus::Active, LiveClassStatus::ReSchedule, LiveClassStatus::ReActivate, LiveClassStatus::Running];
            $sessionLatest = PartnerLiveClassSchedule::where('live_class_id', $liveClass->id)
                                            // ->whereDate('start_date_time', now()->format('Y-m-d'))
                                            ->whereIn('status', $allStatus )
                                            ->whereDate('start_date_time', '>=', now()->toDateString())
                                            ->orderBy('start_date_time', 'ASC')
                                            ->first();

            if ($sessionLatest) {
                $isNotExpired = false;
                $endTime = $sessionLatest->start_date_time->addMinutes($liveClass->duration);

                // if ($endTime->gt(now()) and $sessionLatest->start_date_time->isToday() ) {
                //     $isNotExpired = true;
                // }

                if ($endTime->gt(now())) {
                    $isNotExpired = true;
                }

                if ($sessionLatest->status ==  LiveClassStatus::Running and $isNotExpired and
                    $vendorClasses->publish_status == ClassPublishStatus::Published
                    && $data['is_bought'] and  $isAllow ) {

                    $data['session_id'] =  $sessionLatest->id;
                    $data['internal_id'] = $sessionLatest->internal_id;

                }
                $data['meeting_id'] = $sessionLatest->meeting_id;
                if (in_array($sessionLatest->status, [LiveClassStatus::Suspended, LiveClassStatus::Expired, LiveClassStatus::Completed]) ) {
                    $data['meeting_id'] = "";
                }

                if ($data['meeting_id'] and $endTime->lt(now())) {
                    $data['meeting_id'] = "";
                }

            }

        }


        $data['payment'] = $payment;
        // $data['is_free'] = $is_free;

        if ($liveClass) {
            $classSchedules = [];
            $schedules = PartnerLiveClassSchedule::where('live_class_id', $liveClass->id)
                                                    ->whereDate('start_date_time', '>=', now()->toDateString())
                                                    ->take(5)->get();

            // foreach($schedules as $key => $schedule){
            //     $key ++;
            //     $startDateTime  = Carbon::parse($schedule->start_date_time);

            //     $endTime = $startDateTime->addMinutes((int)$liveClass->duration);

            //     $status = $this->status;
            //     if ($endTime->lt(now()) and in_array($this->status, [LiveClassStatus::Active, LiveClassStatus::Expired, LiveClassStatus::ReActivate, LiveClassStatus::ReSchedule])) {
            //         $status =  LiveClassStatus::Expired;
            //     }

            //     if ($status == LiveClassStatus::ReActivate) {
            //         $status =  LiveClassStatus::Active;
            //     }

            //     $startDateTimeFunc  = Carbon::parse($schedule->start_date_time);
            //     $classSchedules[] = [
            //         'date'          => $startDateTimeFunc,
            //         'day'           => $startDateTimeFunc->format('d'),
            //         'month_year'    => $startDateTimeFunc->format('M Y'),
            //         'title'         => $startDateTimeFunc->format('D'),
            //         'start_time'    => $startDateTimeFunc->format('h:i A'),
            //         'duration'      => $this->convertToHoursMins((int)$liveClass->duration, '%02d h %02d m'),
            //         'live_class_id' => $liveClass->id,
            //         'class_id'      => $this->vendor_class_id,
            //         "status"        => LiveClassStatus::getKey( $status),
            //         'id'            => $schedule->id,
            //         'internal_id'   => $schedule->internal_id,
            //         'meeting_id'    => $schedule->meeting_id
            //     ];
            // }
            $data['schedules'] =  new LiveClassSessionCollection($schedules);
        }else{
            $data['schedules'] = [];
        }

        $trainer_image = "";
        $trainer_name = "";
        // $trainee_partner = false;
        if (isset($data['trainer_image']) and $data['trainer_image']) {
            $trainer_image = $data['trainer_image'];
            // $trainee_partner = true;


        }

        if (isset($data['trainer_name']) and $data['trainer_name']) {
            $trainer_name = Str::title($data['trainer_name']);

        }

        // $data['trainee_partner'] = $trainee_partner;

        if (!$trainer_name) {
            $teacher = DB::connection('partner_mysql')->table('class_teacher')->where('vendorclass_id',  $this->vendor_class_id)->where('isActive', true)->first();
            if ($teacher) {
                $users = UserHelper::users([$teacher->user_id]);
                // $teacherUser = User::where('id', $teacher->user_id)->first();
                if (count( $users)) {
                    $teacherUser = $users->first();
                    $trainer_name = Str::title($teacherUser->name);
                    $trainer_image = $teacherUser->avatar;
                    $data['trainee_partner'] = true;
                }
            }
        }

        if ( $vendorClasses ) {

            $start_time = $this->dateTimeFormat($vendorClasses->start_time);
            $data['start_time'] = $start_time;

            $end_time = $this->dateTimeFormat($vendorClasses->end_time);
            $data['end_time'] = $end_time;
        }


        $data['trainer_name'] = $trainer_name;
        $data['trainer_image'] = $trainer_image;

        return $data;
    }

    private function classBuyStatusData($data)
    {
        $user = auth()->user();
        $data['allow_other'] = false;
        $data['is_bought'] = false;
        if ($user) {
            $userIds = UserHelper::getStudentsIds();
            $ordersCount = Order::whereIn('purchaser_id', $userIds)->where('payment_status', PaymentStatus::Received)->count();
            if ($ordersCount) {
                $data['is_bought'] = true;
                if (count($userIds) > $ordersCount) {
                    $data['allow_other'] = true;
                }
            }
        }

        return $data;
    }

    private function addOthers($data)
    {
        $user = auth()->user();
        $is_payment_done = false;
        $payment = [];
        $data['frequency'] = '1';
        $data['is_live'] = true;
        $data['service'] = 26;
        if ($user) {
            $userIds = UserHelper::getStudentsIds();
            $order = Order::whereIn('purchaser_id', $userIds)->where('payment_status', PaymentStatus::Received)->latest()->first();

            if ($order) {
                $is_payment_done = true;
            }
            // {
            //     is_live : true
            //     frequency: 3",
            //     service: 26,
            //     schedule : [],
            //     payment: {
            //        "id" : 1",
            //        "currency" : "currency sign",
            //        "payment date" : "",
            //         "ammount" : "",
            //     },
        }
    }

    private function vendorClass($data)
    {
        $liveclass = DB::connection('partner_mysql')->table('live_classes')->where('vendor_class_id', $this->vendor_class_id)->first();
        $data['packages'] = [];
        $data['live_class'] = false;
        $data['is_free'] = false;
        $is_free = false;
        if ($liveclass) {
            $data['live_class'] = true;
            if ($liveclass->is_free) {
                $data['is_free'] = true;
                $is_free = true;
            }
        }

        if ($is_free) {
            return $data;
        }

        $packagesIds = DB::connection('partner_mysql')->table('class_fee')
                        ->where('vendor_class_id', $this->vendor_class_id)
                        ->where('isActive', 1)->pluck('package_id')->get();

        $discountsIds = DB::connection('partner_mysql')->table('fee_discount')
                        ->whereIn('package_id', $packagesIds)
                        ->where('isActive', 1)->get();

        $packages = Fee::whereIn('id', $packagesIds)->where('isActive', 1)->get();

        $packagesData = $this->packages($packages, $discountsIds);

        $data['packages'] = $packagesData;

        return  $data;
    }

    private function packages($packages, $discountsIds)
    {
        $packagesList = [];
        foreach ($packages as $package) {
            $offersIds = $discountsIds->where('package_id', $package->id)->pluck('id')->get();
            $offers = Discount::whereIn('id', $offersIds)->get();
            $packagesList[] = [
                'id'                  => $package->id,
                'name'                => Str::title($package->name),
                'amount'              => '₹'.number_format($package->amount, 2),
                'validity'            => $package->validity.' WEEKS',
                'offers'              => $this->offersFun($offers),
            ];
        }

        return $packagesList;
    }

    private function offersFun($packageOffers)
    {
        $offers = [];
        $offers = count($packageOffers) ? $packageOffers->reverse() : [];
        $datas = [];
        $amount = '0';
        $type = '₹';
        foreach ($offers as $offer) {
            $endDate = $offer->end_date;

            if ($offer->isPercentage) {
                $amount = ($this->amount * $offer->value) / 100;
            } else {
                $amount = $offer->value;
            }
            $amount = $type.number_format($amount, 2);

            $data = [
                'name'        => Str::title($offer->name),
                'description' => $offer->description,
                'code'        => strtoupper($offer->code),
                'amount'      => $amount,
                'isActive'    => true,
                'id'          => $offer->id,
            ];

            if ($offer->isActive and $offer->type == DiscountType::Offer) {
                $isFuture = false;
                $now = now();
                $currentDate = Carbon::createFromFormat('d/m/Y', $now->format('d/m/Y'))->startOfDay();
                $startDate = $offer->start_date->startOfDay();

                if ($currentDate->lt($startDate)) {
                    $isFuture = true;
                }

                if ($isFuture) {
                    $data['isActive'] = false;
                }
                if ($endDate) {
                    $endDate = $offer->end_date->startOfDay();
                    if ($currentDate->gt($endDate)) { // checking if offer expired or not
                        $data['isActive'] = false;
                    }
                }
                $datas[] = $data;
            }
        }

        return $datas;
    }

    private function addServices($data, $content)
    {
        $service = '';
        $service_id = '';
        $service_icon = '';

        $data['language'] = '';
        $data['language_id'] = 0;

        if ($content and isset($content->service_id) and $content->service_id) {
            $serviceData = Service::where('id', $content->service_id)->first();
            $service = $serviceData->name;
            $service_id = $serviceData->id;

            $imageUrl = public_path('/').'images/servicesMobile/service'.$content->service_id.'.svg';
            if (is_file($imageUrl)) {
                $service_icon = url('/').'/images/servicesMobile/service'.$content->service_id.'.svg';
            } else {
                $service_icon = url('/').'/images/servicesMobile/service1.svg';
            }
        }
        if (! $service_icon) {
            $service_icon = url('/').'/images/servicesMobile/service1.svg';
        }
        $data['service'] = $service;
        $data['service_icon'] = $service_icon;
        $data['service_id'] = $service_id;
        $data['live_class'] = false;
        $data['class_id'] = $this->vendor_class_id ?? 0;
        $data['live_class_id'] = 0;

        if (! $this->isdetails) {
            $liveclass = DB::connection('partner_mysql')->table('live_classes')->where('vendor_class_id', $this->vendor_class_id)->first();
            if ($liveclass) {
                $data['live_class'] = true;
                $data['live_class_id'] = $liveclass->id;
            }
        }

        if (isset($content->language_id) and $content->language_id) {
            $languageData = DB::connection('partner_mysql')->table('languages')->where('id', $content->language_id)->first();
            if ($languageData) {
                $data['language'] = $languageData->title;
                $data['language_id'] = $languageData->id;
            }
        }

        return $data;
    }

    private function convertToHoursMins($time, $format = '%02d:%02d')
    {
        if ($time <= 60) {
            return $time.'m';
        }

        $hours = floor($time / 60);
        $minutes = ($time % 60);

        return sprintf($format, $hours, $minutes);
    }

    private function bindBoughtData($data, $bought_data)
    {
        if ($bought_data) {
            $data['is_bought'] = $bought_data['is_bought'];
            $data['allow_other'] = $bought_data['allow_other'];
        } else {
            $data['is_bought'] = false;
            $data['allow_other'] = false;
        }

        return $data;
    }

    private function bindSessionData($data, $sessionData)
    {
        if ($sessionData) {
            $data['meeting_id'] = $sessionData['meeting_id'];
            $data['session_id'] = $sessionData['session_id'];
            $data['internal_id'] = $sessionData['internal_id'];
        } else {
            $data['meeting_id'] = '';
            $data['session_id'] = 0;
            $data['internal_id'] = '';
        }

        return $data;
    }

    private function dateTimeFormat($date, $isFormat = true)
    {
        try {
            if ($isFormat) {
                return Carbon::parse($date)->format('g:i A');
            }
            return Carbon::parse($date);
        } catch (\Throwable $th) {
            try {
                if ($isFormat) {
                    return Carbon::parse($date)->format('g:i A');
                }
                return Carbon::parse($date);
            } catch (\Throwable $th) {
            }
        }

        return '';
    }

    private function checkDateStatus($datas, $saved_content)
    {

        try {
            if(isset($datas['end_date_time']) and $datas['end_date_time'] and $datas['end_date_time']->isPast()) {
                if (isset($datas['end_time_parse']) and $datas['end_time_parse']) {
                    $dateTime = $datas['end_date_time']->format('d/m/Y'). ' '.$datas['end_time_parse']->format('h:i A');
                    $endDateTime = Carbon::createFromFormat('d/m/Y h:i A', $dateTime);
                    if ($endDateTime->isPast()) {
                        // $datas['allow_other'] = false;
                        // $datas['is_bought'] = true;
                        $datas['is_expired'] = true;
                    }
                }else{
                    // $datas['allow_other'] = false;
                    // $datas['is_bought'] = true;
                    $datas['is_expired'] = true;
                }

            }else{
                $datas['is_expired'] = false;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        if ($saved_content and isset($saved_content->session_price)) {
            $session_price = $saved_content->session_price;
            $datas['display_price'] =  config('client.currency') ."". $session_price;
        }else{
            $datas['display_price'] = config('client.currency') ."".(string)$datas['product_price'];
        }

        return $datas;
    }

    private function addMedia($datas, $published_content)
    {

        if ($this->collection_type == CollectionType::campaignsType) {
            if (isset($published_content->images) and count($published_content->images)) {
                $image = $published_content->images[0];
                $datas['featured_image'] = Storage::url($image->url);
            }
            return $datas;
        }


        $this->load('medias');
        if (count($this->medias)) {
            $mediaData = $this->medias[0];
            // $datas['featured_image'] = $published_content->featured_image;
            if ($mediaData) {
                $datas['featured_image'] = $mediaData;
                if ($datas['featured_image'] && $datas['featured_image']['url']) {
                    $datas['featured_image'] = Storage::disk('s3')->url($datas['featured_image']['url']);
                }
            }
        } else {
            if (isset($published_content->featured_image) && $published_content->featured_image) {
                $datas['featured_image'] = $published_content->featured_image;
                if ($published_content->featured_image) {
                    $datas['featured_image'] = $published_content->featured_image;
                    if ($published_content->featured_image && isset($published_content->featured_image->url)) {
                        $datas['featured_image'] = Storage::disk('s3')->url($datas['featured_image']->url);
                    } else {
                        if (isset($datas['featured_image']) and $datas['featured_image'] and $datas['featured_image']->url) {
                            $datas['featured_image'] = Storage::disk('s3')->url($datas['featured_image']->url);
                        }
                    }
                }
            }
        }

        return $datas;
    }

    private function getDynamicUrl()
    {
        if (!$this->relationLoaded('dynamicurls')) {
            $this->load('dynamicurls');
        }

        if ($this->dynamicurls->count() > 0) {
            return $this->dynamicurls->last()->url;
        }
        return null;
    }
}
