<?php

namespace App\Http\Resources;

use App\Collection as AppCollection;
use App\Enums\CollectionType;
use App\Enums\PublishStatus;
use App\Enums\ReviewStatus;
use App\Helpers\CollectionHelper;
use App\Http\Resources\CommentCollection;
use App\Http\Resources\WebDataCollection;
use App\Media;
use App\Model\Partner\Service;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WebData extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = auth()->user();
        if ($this->mobile and ! $this->isdetails) {
            if (isset($this->ischeck) && $this->ischeck) {
                $published_content = $this->published_content;
            } else {
                $published_content = json_decode($this->published_content);
            }
            $featured_image = null;
            $isImage = false;
            if (isset($published_content->featured_image) && $published_content->featured_image) {
                $featured_url = $published_content->featured_image->url;
                if (! Str::endsWith($featured_url, ['.png', '.jpeg'])) {
                    if (!str_contains($featured_url, 'http')) {
                        $featured_image = Storage::url($featured_url);
                    }else{
                        $featured_image = $featured_url;
                    }
                } else {
                    $featured_image = $featured_url;
                    $isImage = true;
                }
            }
            $datas = [
                'id'              => $this->id,
                'title'           => Str::title($this->title),
                'slug'            => $this->slug,
                'featured_image'  => $featured_image,
                'is_featured'     => $this->is_featured ? true : false,
                'is_recommended'  => $this->is_recommended ? true : false,
                'is_image'        => $isImage,
                //'dynamicUrl'      => ''
            ];

            // if ($this->can_drafted) {
            //     $saved_content = json_decode($this->saved_content);
            //     if (isset($saved_content->featured_image)  && $saved_content->featured_image && $saved_content->featured_image->id) {
            //         $datas_featured_image = $saved_content->featured_image;
            //         $media = Media::find($saved_content->featured_image->id);
            //         if ($media) {
            //             $media->url = Storage::url($media->url);
            //             $datas_featured_image = $media;
            //         }
            //         if (isset($$datas_featured_image['url'])) {
            //             $datas['featured_image'] = $datas_featured_image;
            //         }
            //     }
            // }

            return $datas;
        } else {
            $datas = [
                'id' => $this->id,
                'title' => $this->title,
                'collection_type' => $this->collection_type,
                'collection_type_name' => $this->collection_type ? ucwords(preg_replace('/([a-z])([A-Z])/', '\\1 \\2', (CollectionType::getKey($this->collection_type)))) : null,
                'is_featured' => $this->is_featured ? $this->is_featured : false,
                'is_recommended' => $this->is_recommended ? $this->is_recommended : false,
                'slug' => $this->slug,
                'head_category' => '',
                'country' => '',
                'featured_image' => '',
                'is_indian' => true,
                'categories' => [],
                'tags' => [],
                'featured_tags' => [],

            ];
        }

        if ($this->mobile) {
            $datas['is_featured'] = $datas['is_featured'] ? true : false;
            $datas['is_recommended'] = $datas['is_recommended'] ? true : false;
        }

        $datas['vendor_id'] = 0;
        $datas['vendor_class_id'] = 0;
        $datas['product_price'] = 0;
        if ($this->vendor_id) {
            $datas['vendor_id'] = $this->vendor_id;
        }

        if ($this->vendor_class_id) {
            $datas['vendor_class_id'] = $this->vendor_class_id;
        }

        $productPrice = null;
        if (in_array($this->collection_type, [CollectionType::classes, CollectionType::classDeck, CollectionType::events, CollectionType::workshops])) {
            $productPrice = CollectionHelper::getActivePackage($this);
        }
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
        $published_content = null;
        if ($this->published_content) {
            if (isset($this->ischeck) && $this->ischeck) {
                $published_content = $this->published_content;
            } else {
                $published_content = json_decode($this->published_content);
            }

            $datas = $this->addServices($datas, $published_content); // add services

            $datas = $this->bindBoughtData($datas, $this->bought_data);

            $datas['highlights'] = (isset($published_content->highlights) && $published_content->highlights) ? $published_content->highlights : null;

            if (isset($published_content->featured_image) && $published_content->featured_image) {
                $featured_url = $published_content->featured_image->url;
                // $featured_relative_path = '/cms' . array_reverse(explode('/cms', $featured_url))[0];
                $published_content->featured_image->url = Storage::url($featured_url);
                $datas['featured_image'] = $published_content->featured_image;
            }
            if (isset($published_content->author_image) && $published_content->author_image) {
                $authorUrl = $published_content->author_image->url;
                // $authorRelativePath = '/cms' . array_reverse(explode('/cms', $featured_url))[0];
                $published_content->author_image->url = Storage::url($authorUrl);
                $datas['author_image'] = $published_content->author_image;
            }

            if (isset($published_content->featured_video) && $published_content->featured_video) {
                $datas['featured_video'] = $published_content->featured_video;
            }
            $datas['excerpt'] = (isset($published_content->excerpt) && $published_content->excerpt) ? $published_content->excerpt : null;
            $datas['source'] = (isset($published_content->source) && $published_content->source) ? $published_content->source : null;
            $datas['date'] = (isset($published_content->date) && $published_content->date) ? $published_content->date : null;
            $datas['location'] = (isset($published_content->location) && $published_content->location) ? $published_content->location : null;
            $datas['state'] = (isset($published_content->state) && $published_content->state) ? $published_content->state : null;
            if (isset($published_content->country) && $published_content->country) {
                $datas['country'] = $published_content->country;
                if ($published_content->country && isset($published_content->country->id)) {
                    $datas['is_indian'] = $published_content->country->id == \App\Enums\Countries::India;
                }
            }
            if (isset($published_content->categories) && $published_content->categories && count($published_content->categories) > 0) {
                $datas['categories'] = $published_content->categories;
                $datas['head_category'] = isset($published_content->categories[0]->name) ? Str::title($published_content->categories[0]->name) : '';
            }
            if (isset($published_content->tags) && $published_content->tags && count($published_content->tags) > 0) {
                $datas['tags'] = $published_content->tags;
            }

            $datas['content'] = (isset($published_content->content) && $published_content->content) ? $published_content->content : null;

            $datas['read_time'] = (isset($published_content->read_time) && $published_content->read_time) ? $published_content->read_time : null;
            $datas['author_name'] = (isset($published_content->author_name) && $published_content->author_name) ? $published_content->author_name : '';
            if (isset($published_content->author_designation) && $published_content->author_designation) {
                $datas['author_designation'] = $published_content->author_designation;
            }
            if (isset($published_content->author_description) && $published_content->author_description) {
                $datas['author_description'] = $published_content->author_description;
            }
            if (isset($published_content->author_experience) && $published_content->author_experience) {
                $datas['author_experience'] = $published_content->author_experience;
            }
            if (isset($published_content->profile)) {
                $datas['email'] = ($published_content->profile && isset($published_content->profile->email)) ? $published_content->profile->email : null;
                $datas['website'] = ($published_content->profile && isset($published_content->profile->website)) ? $published_content->profile->website : null;
                $datas['linkedIn'] = ($published_content->profile && isset($published_content->profile->linkedIn)) ? $published_content->profile->linkedIn : null;
                $datas['twitter'] = ($published_content->profile && isset($published_content->profile->twitter)) ? $published_content->profile->twitter : null;
                $datas['facebook'] = ($published_content->profile && isset($published_content->profile->facebook)) ? $published_content->profile->facebook : null;
            }
            $datas['organisation_name'] = '';

            if ($this->collection_type == CollectionType::testimonials) {
                $datas['author_company'] = isset($published_content->author_company) ? $published_content->author_company : null;
                $datas['author_designation'] = isset($published_content->author_designation) ? $published_content->author_designation : null;
            } elseif ($this->collection_type == CollectionType::panelList) {
                $datas['image_gallery'] = (isset($published_content->images) && $published_content->images) ? $published_content->images : [];
            } elseif ($this->collection_type == CollectionType::sponsers) {
                $datas['name'] = $this->title;
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
                $datas['collection_type_name'] = 'Partner Gallery';

                // $datas['featured_image'] = isset($published_content->images) && count($published_content->images) > 0 ? $published_content->images[0] : [];
                $datas['images'] = isset($published_content->images) && count($published_content->images) ? $published_content->images : [];
            } elseif ($this->collection_type == CollectionType::people) {
                $datas['collection_type_name'] = 'People';
            // $datas['featured_image'] = $published_content->featured_image && $published_content->featured_image->url ? $published_content->featured_image : '';
            } elseif ($this->collection_type == CollectionType::awards) {
                // $datas['image_gallery'] = (isset($published_content->images) && $published_content->images) ? $published_content->images : [];
                $datas['image_gallery'] = '';
                if (isset($published_content->images) && $published_content->images) {
                    $images = $published_content->images;
                    foreach ($images as $img) {
                        $img->url = Storage::url($img->url);
                    }
                    $datas['image_gallery'] = $images;
                }
            } elseif ($this->collection_type == CollectionType::events || $this->collection_type == CollectionType::workshops || $this->collection_type == CollectionType::classes
            || $this->collection_type == CollectionType::classDeck
            ) {
                if ($this->collection_type == CollectionType::workshops) {
                    $datas['collection_type_name'] = 'Workshop';
                } elseif ($this->collection_type == CollectionType::classes) {
                    $datas['collection_type_name'] = 'Class';
                } elseif ($this->collection_type == CollectionType::classDeck) {
                    $datas['collection_type_name'] = 'Live Class';
                } else {
                    $datas['collection_type_name'] = 'Event';
                }

                $datas['start_date'] = (isset($published_content->start_date) && $published_content->start_date) ? $published_content->start_date : null;
                $datas['end_date'] = (isset($published_content->end_date) && $published_content->end_date) ? $published_content->end_date : null;
                $datas['start_time'] = (isset($published_content->start_time) && $published_content->start_time) ? $published_content->start_time : null;
                $datas['end_time'] = (isset($published_content->end_time) && $published_content->end_time) ? $published_content->end_time : null;
                $datas['fee'] = (isset($published_content->fee) && $published_content->fee) ? $published_content->fee : null;
                $datas['author_image'] = (isset($published_content->author_image) && $published_content->author_image) ? $published_content->author_image : null;

                if (isset($datas['end_date']) and $datas['end_date']) {
                    $endDate = Carbon::createFromFormat('Y/m/d', $datas['end_date']);
                    $datas['end_date_time'] = $endDate;
                }

                if (isset($datas['end_time']) and $datas['end_time']) {
                    $datas['end_time_parse'] = $this->dateTimeFormat($datas['end_time'], false);
                }

                $datas = $this->checkDateStatus($datas, $published_content);

                if (isset($this->product) && $this->product) {
                    $datas['product'] = $this->product;
                    $datas['product_review'] = $this->product->productReviews;
                    $datas['coupons'] = $this->product->coupons;
                    $this->product->unsetRelation('productReviews')->unsetRelation('coupons');
                }
                $datas['rating_percentage'] = null;
                // $datas['product_price'] = (int)$this->published_price ??  0;
                // $datas['original_product_price'] = (isset($this->product) && isset($this->product->prices) && count($this->product->prices) > 0)  ? $this->product->prices[0]->price : 0;
                // $datas['product_price_id'] = (isset($this->product) && isset($this->product->prices) && count($this->product->prices) > 0)  ? $this->product->prices[0]->id : '';

                $datas['original_product_price'] = $original_product_price;
                $datas['product_price_id'] = $product_price_id;

                $datas['product_price'] = 0;

                if (isset($datas['original_product_price']) and $datas['original_product_price']) {
                    // $datas['product_price'] = (int)$this->published_price ??  0;
                    // $datas['product_price'] = $this->published_price ??  $datas['original_product_price'] ? $datas['original_product_price'] : 0;
                    if ($this->published_price) {
                        $datas['product_price'] = $this->published_price;
                    } else {
                        $datas['product_price'] = $datas['original_product_price'];
                    }
                }

                $datas['discount_amount'] = 0;
                $datas['additional_threshold'] = null;
                $datas['additional_amount'] = null;
                $datas['isPercentage'] = null;
                // if ((isset($this->product) && isset($this->product->prices) && count($this->product->prices) > 0 && $this->product->prices[0]->discounts)) {
                //     $discount = $this->product->prices[0]->discounts;
                //     if (Carbon::parse($discount->start_date)->isPast() && !Carbon::parse($discount->end_date)->endOfDay()->isPast()) {
                //         $datas['discount_amount'] = $discount  ? $discount->amount : 0;
                //         $datas['additional_threshold'] = $discount  ? $discount->additional_threshold : null;
                //         $datas['additional_amount'] = $discount  ? $discount->additional_amount : null;
                //         $datas['isPercentage'] = $discount  ? $discount->is_percentage : null;
                //     }
                // }

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
                    // $datas['rating_percentage'] = round(($count / (count($this->product->productReview) * 5)) * 100, 2);
                    // $datas['rating_percentage'] = $count;
                }
            } elseif ($this->collection_type == CollectionType::carnivalActivitites) {
                $datas['start_date'] = (isset($published_content->start_date) && $published_content->start_date) ? $published_content->start_date : null;
                $datas['start_time'] = (isset($published_content->start_time) && $published_content->start_time) ? $published_content->start_time : null;
                $datas['collection_type_name'] = 'Activities';
            } elseif ($this->collection_type == CollectionType::news) {
                $datas['source_name'] = (isset($published_content->source_name) && $published_content->source_name) ? $published_content->source_name : null;
            } elseif ($this->collection_type == CollectionType::caseStudy) {
                $datas['collection_type_name'] = 'Young Xpert';
                $datas['dynamicurl'] = $this->getDynamicUrl() ? $this->getDynamicUrl() :'';
            }

            // Details
            if ($this->isdetails) {

                if ($this->collection_type == CollectionType::blogs) {
                    $this->load('comments.user');
                    $datas['comments'] = count($this->comments) > 0 ? new CommentCollection($this->comments) : [];
                }

                if (isset($published_content->featured_image) && $published_content->featured_image) {
                    $datas['featured_image'] = $published_content->featured_image;
                    $this->load('medias');
                    $medias = $this->medias->toArray();
                    if ($medias && count($medias) > 0) {
                        $datas['featured_image'] = $medias[0];
                        if ($datas['featured_image'] && $datas['featured_image']['url']) {
                            $datas['featured_image']['url'] = Storage::url($datas['featured_image']['url']);
                        }
                    }
                }

                if ($this->collection_type == CollectionType::startups) {
                    $datas['location'] = (isset($published_content->location) && $published_content->location) ? $published_content->location : null;
                    // $datas['employees'] = (isset($published_content->employees) && $published_content->employees) ? $published_content->employees : null;
                    $datas['collection_type_name'] = 'Partner';
                    $datas['contact'] = (isset($published_content->profile) && isset($published_content->profile->contact) ? $published_content->profile->contact : '');

                    $id = [];
                    if (isset($published_content->founders) && $published_content->founders) {
                        $id = array_merge($id, array_map(function ($tag) {
                            return $tag->id;
                        }, $published_content->founders));
                    }
                    if (isset($published_content->services) && $published_content->services) {
                        $id = array_merge($id, array_map(function ($tag) {
                            return $tag->id;
                        }, $published_content->services));
                    }
                    if (isset($published_content->events) && $published_content->events) {
                        $id = array_merge($id, array_map(function ($tag) {
                            return $tag->id;
                        }, $published_content->events));
                    }
                    if (isset($published_content->workshops) && $published_content->workshops) {
                        $id = array_merge($id, array_map(function ($tag) {
                            return $tag->id;
                        }, $published_content->workshops));
                    }
                    if (isset($published_content->gallery) && $published_content->gallery) {
                        $id = array_merge($id, array_map(function ($tag) {
                            return $tag->id;
                        }, $published_content->gallery));
                    }

                    $sub_collections = AppCollection::whereIn('id', $id)->get();
                    // $sub_collections->map(function ($col) {
                    //     $col['can_drafted'] = true;
                    // });

                    $subCollectionData = new WebDataCollection($sub_collections);

                    $event_worshop = [CollectionType::events, CollectionType::workshops];
                    $datas['founders'] = $subCollectionData->where('collection_type', CollectionType::people)->toArray();
                    $datas['services'] = $subCollectionData->where('collection_type', CollectionType::services)->toArray();
                    $datas['events'] = $subCollectionData->whereIn('collection_type', $event_worshop)->toArray();
                    $datas['galleries'] = $subCollectionData->where('collection_type', CollectionType::partnerGalleries)->toArray();
                // $datas['founders'] = $subCollectionData->where('collection_type', CollectionType::people)->toArray();
                } elseif ($this->collection_type == CollectionType::panelList) {
                    $id = [];
                    if (isset($published_content->events) && $published_content->events) {
                        $id = array_merge($id, array_map(function ($tag) {
                            return $tag->id;
                        }, $published_content->events));
                    }

                    if (isset($published_content->workshops) && $published_content->workshops) {
                        $id = array_merge($id, array_map(function ($tag) {
                            return $tag->id;
                        }, $published_content->workshops));
                    }

                    $sub_collections = AppCollection::whereIn('id', $id)->get();
                    $sub_collections->map(function ($col) {
                        $col['can_drafted'] = true;
                    });

                    $subCollectionData = new WebDataCollection($sub_collections);

                    $event_worshop = [CollectionType::events, CollectionType::workshops];
                    $datas['events'] = $subCollectionData->whereIn('collection_type', $event_worshop)->toArray();
                } elseif ($this->collection_type == CollectionType::caseStudy) {
                    $images = $this->mobile ? [] : '';

                    if (isset($published_content->images) && $published_content->images) {
                        $images = $published_content->images;
                        $imageDatas = [];
                        foreach ($images as $img) {
                            if (!Str::endsWith($img->url, ['.png', '.jpeg'])) {
                                $imgUrl = Storage::url($img->url);
                                $img->url = $imgUrl;
                                $imageDatas[] = $imgUrl;
                            } else {
                                $imageDatas[] = $img->url;
                            }
                        }

                        if ($this->mobile) {
                            $images = $imageDatas;
                        }
                    }

                    $datas['image_gallery'] = $images;
                    $videos = $this->mobile ? [] : '';
                    if ((isset($published_content->featured_videos) && $published_content->featured_videos)) {
                        $videos = $published_content->featured_videos;
                    }
                    $datas['featured_videos'] = $videos;
                }

                $this->load('seos');
                if ($this->seos) {
                    $datas['meta'] = json_decode($this->seos->meta);
                }
                if (
                    $this->collection_type == CollectionType::news ||
                    $this->collection_type == CollectionType::articles ||
                    $this->collection_type == CollectionType::caseStudy
                ) {
                    $allBlogs = AppCollection::where('collection_type', $this->collection_type)
                        ->whereNotNull('published_content')
                        ->latest()
                        ->select('id', 'title', 'slug')
                        ->get();

                    $datas['previous'] = $allBlogs->where('id', '<', $datas['id'])->first();
                    $datas['next'] = $allBlogs->where('id', '>', $datas['id'])->last();

                    if ($datas['previous'] == null) {
                        $datas['previous'] = $allBlogs->first();
                    }
                    if ($datas['next'] == null) {
                        $datas['next'] = $allBlogs->last();
                    }
                }
            }
        }

        // Sub Collections data only
        if ($this->can_drafted) {
            $saved_content = json_decode($this->saved_content);
            if (isset($saved_content->featured_image) && $saved_content->featured_image && $saved_content->featured_image->id) {
                $datas['featured_image'] = $saved_content->featured_image;
                $media = Media::find($saved_content->featured_image->id);
                if ($media) {
                    $media->url = Storage::url($media->url);
                    $datas['featured_image'] = $media;
                }
            }

            $datas['author_name'] = (isset($saved_content->author_name) && $saved_content->author_name) ? $saved_content->author_name : '';

            if (isset($saved_content->author_designation) && $saved_content->author_designation) {
                $datas['author_designation'] = $saved_content->author_designation;
            }

            if (isset($saved_content->profile)) {
                $datas['linkedIn'] = ($saved_content->profile && isset($saved_content->profile->linkedIn)) ? $saved_content->profile->linkedIn : null;
                $datas['twitter'] = ($saved_content->profile && isset($saved_content->profile->twitter)) ? $saved_content->profile->twitter : null;
                $datas['facebook'] = ($saved_content->profile && isset($saved_content->profile->facebook)) ? $saved_content->profile->facebook : null;
            }
            $datas['excerpt'] = (isset($saved_content->excerpt) && $saved_content->excerpt) ? $saved_content->excerpt : null;
            $datas['date'] = (isset($saved_content->date) && $saved_content->date) ? $saved_content->date : null;
            $datas['read_time'] = (isset($saved_content->read_time) && $saved_content->read_time) ? $saved_content->read_time : null;
            $datas['source'] = (isset($saved_content->source) && $saved_content->source) ? $saved_content->source : null;

            if (isset($published_content->tags) && $published_content->tags && count($published_content->tags) > 0) {
                $datas['tags'] = $published_content->tags;
                // $datas['head_category'] =  $published_content->tags[0]->name;
            }
            if (isset($published_content->featured_tags) && is_array($published_content->featured_tags) && count($published_content->featured_tags) > 0) {
                // $datas['tags'] =  $published_content->tags;
            }

            $datas['content'] = (isset($saved_content->content) && $saved_content->content) ? $saved_content->content : null;
            if (isset($saved_content->highlights) && $saved_content->highlights) {
                $datas['highlights'] = $saved_content->highlights;
            }
        }
        $this->load('likes');
        $likes = $this->likes;
        $datas['likeCount'] = count($likes) ? $likes->where('is_liked', 1)->count() : 0;
        $authLiked = false;
        if ($user && $user->id) {
            $authLiked = count($likes) ? $likes->where('is_liked', 1)->where('created_by', $user->id)->count() ? true : false : false;
        }
        // if(count($this->likes) > 0 && $user && $user->id) {
        //     foreach($this->likes as $like){
        //         if($like->created_by == $user->id){
        //             $authLiked = true;
        //         }
        //     }
        // }

        $datas['authLiked'] = $authLiked;

        if ($this->collection_type == CollectionType::caseStudy) {
            return $this->caseStudy($datas);
        }

        if ($this->collection_type == CollectionType::campaigns) {
            return $this->caseCampaigns($datas,  $published_content);
        }

        return $datas;
    }



    private function caseStudy($data)
    {
        $webUrl = config('app.client_url').'/young_xpert/'.$data['slug'];
        $data['web_url'] =  $webUrl;
      

        return $data;
    }

    private function caseCampaigns($data,  $published_content)
    {
        if (!$data['featured_image']) {
            $images  = [];
            if (isset($published_content->images) and $published_content->images and count($published_content->images)) {
                foreach ($published_content->images as $image) {
                    $images[] = $image->full_url;
                }
            }
            if (count($images)) {
                $data['featured_image'] = $images[0];
            }
        }

        return $data;
    }

    public function getReviewCount($cat)
    {
        $categories = [];
        foreach ($cat as $data) {
            if ($data->name != null) {
                $categories['category_name'][] = $data->name;
            }
        }

        return $categories;
    }

    private function addServices($data, $content)
    {
        $service = '';
        $service_id = '';
        $service_icon = '';

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

        return $data;
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
            $datas['display_price'] = $session_price;
        }else{

            $datas['display_price'] = (string)$datas['product_price'];
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
