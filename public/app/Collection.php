<?php

namespace App;

use App\Enums\PaymentStatus;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class Collection extends Model
{
    // use Searchable;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'published_content', 'saved_content', 'created_by',
        'updated_by', 'published_by', 'published_at', 'deleted_at', 'vendor_id',
        'status', 'is_private', 'collection_type', 'is_featured', 'is_recommended', 'published_price',
        'categories', 'services', 'tags', 'location', 'vendor_class_id',
    ];

    protected $logAttributes = [
        'title', 'saved_content', 'published_content', 'is_featured', 'is_recommended', 'categories', 'tags', 'status',
        'published_by', 'published_at', 'created_by', 'updated_by', 'deleted_at',
    ];

    protected $casts = [

        'services'      => 'array',
    ];

    protected static $ignoreChangedAttributes = ['created_at', 'updated_at', 'collection_type'];
    protected static $logOnlyDirty = true;

    // protected $searchAttributes = ['id', 'title', 'published_content'];

    // public function searchableAs()
    // {
    //     return 'collection_index';
    // }

    // public function shouldBeSearchable()
    // {
    //     return $this->isPublished();
    // }

    // public function isPublished()
    // {
    //     return $this->attributes['status'] == PublishStatus::Published && $this->attributes['deleted_at'] == null;
    // }

    // public function toSearchableArray()
    // {
    //     $array = $this->toArray();

    //     $filtered = array_intersect_key( $array, array_flip( $this->searchAttributes ) );

    //     return $filtered;
    // }

    /**
     * Prepare a date for array / JSON serialization.
     *
     * @param  \DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function seos()
    {
        return $this->morphOne(\App\Seo::class, 'model');
    }

    public function medias()
    {
        return $this->morphToMany(\App\Media::class, 'mediable')->latest();
    }

    public function mediables()
    {
        return $this->morphMany(\App\Mediables::class, 'mediable');
    }

    public function files()
    {
        return $this->morphToMany(\App\File::class, 'fileables');
    }

    public function fileables()
    {
        return $this->morphMany(\App\Fileable::class, 'mediable');
    }

    public function createdBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'updated_by');
    }

    public function publishedBy()
    {
        return $this->setConnection('mysql2')->belongsTo(\App\User::class, 'published_by');
    }

    public function versions()
    {
        return $this->hasMany(\App\CollectionVersion::class);
    }

    public function versionNames()
    {
        return $this->versions()->select('id', 'version');
    }

    public function categories()
    {
        return $this->belongsToMany(\App\Category::class, 'category_collection_pivots', 'collection_id', 'category_id');
    }

    public function categoryPivot()
    {
        return $this->hasMany(\App\CategoryCollectionPivot::class, 'collection_id', 'id');
    }

    public function tags()
    {
        return $this->belongsToMany(\App\Tag::class, 'tag_collection_pivots', 'collection_id', 'tag_id');
    }

    public function tagPivot()
    {
        return $this->hasMany(\App\TagCollectionPivot::class);
    }

    public function confirmOrders()
    {
        return $this->hasMany(\App\Order::class, 'collection_id')->where('payment_status', PaymentStatus::Received);
    }

    public function product()
    {
        return $this->hasOne(\App\Product::class);
    }

    public function vendor()
    {
        return $this->setConnection('partner_mysql')->belongsTo(\App\Vendor::class, 'vendor_id');
    }

    public function stories()
    {
        return $this->hasMany(\App\Story::class, 'campaign_id');
    }

    public function likes()
    {
        return $this->morphMany(\App\Like::class, 'likable');
    }

    public function comments()
    {
        return $this->morphMany(\App\Comment::class, 'commentable')->where('is_active', 1)->latest();
    }

    public function productReviews()
    {
        return $this->hasMany(\App\ProductReviews::class, 'collection_id');
    }

    public function dynamicurls()
    {
        return $this->morphMany('App\DynamicUrl', 'dynamicurlable');
    }
}
