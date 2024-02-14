<?php

namespace App\Http\Controllers;

use App\Adapters\Subscription\SubscriptionAdapter;
use App\Collection;
use App\Coupon;
use App\Discount;
use App\Enums\CollectionType;
use App\Enums\CouponStatus;
use App\Enums\PaymentStatus;
use App\Enums\PlatformType;
use App\Enums\PublishStatus;
use App\Enums\UserRole;
use App\Enums\VendorRoleType;
use App\Helpers\CodeHelper;
use App\Helpers\CollectionHelper;
use App\Helpers\SlugHelper;
use App\Helpers\TaxFeeHelper;
use App\Http\Resources\Product as ProductResource;
use App\Jobs\SendAttachedNotification;
use App\Jobs\SendBookingNotification;
use App\Lib\Util;
use App\Model\Partner\CollectionOrder;
use App\Model\Partner\Fee;
use App\Model\Partner\StudentRegistration;
use App\Model\Partner\VendorClass;
use App\Model\PartnerCollection;
use App\Model\Student;
use App\Notifications\NewBookingDone;
use App\Order;
use App\Product;
use App\ProductPrice;
use App\ProductReviews;
use App\User;
use App\Vendor;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB as FacadesDB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Validator;

class ProductController extends Controller
{
    /**
     * Get a list of registered products.
     *
     * @param  Request $request
     * @return \App\Product $products
     */
    public function getAllProducts(Request $request)
    {
        $user = auth()->user();
        $products = Product::with('productReviews', 'collection', 'packages')->latest();

        if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
            $products = $products->where('created_by', $user->id);
        }

        if ($request->isTrashed) {
            $products = $products->onlyTrashed();
        }

        if ($request->search) {
            $products = $products->where('name', 'like', "%{$request->search}%");
        }

        if ($request->maxRows) {
            $products = $products->paginate($request->maxRows);
        } else {
            $products = $products->get();
        }

        return $products;
    }

    public function checkNotification(Request $request)
    {
        $orderId = $request->order_id;
        if ($orderId) {
            $order = CollectionOrder::where('id', $orderId)->latest()->first();
        } else {
            $order = CollectionOrder::whereNotNull('collection_id')->where('payment_status', PaymentStatus::Received)->latest()->first();
        }
        $this->sendNotification($order, PaymentStatus::Received);
        return response(['status' => true]);
    }

    public function checkNotificationMail(Request $request)
    {
        $orderId = $request->order_id;
        $type = $request->type;
        if ($orderId) {
            $order = CollectionOrder::where('id', $orderId)->latest()->first();

        }else{
            $order = CollectionOrder::whereNotNull('collection_id')->where('payment_status', PaymentStatus::Received)->latest()->first();

        }

        $order->load('collection');
        $collection = $order->collection;
//        $collection_type_name = $collection->collection_type ? ucwords(preg_replace('/([a-z])([A-Z])/', "\\1 \\2", (CollectionType::getKey($collection->collection_type)))) : null;
        $collection_type_name = Util::collectionTypeLookup($collection->collection_type);
        $data =  [
            'is_partner'          => false,
            'title'               => "",
            'description'         => "",
            'studentName'         => "Student",
            'purchaser'           => "Purchaser",
            "created_at"          => $order->created_at,
            'collection_type_name'=> $collection_type_name
        ];

        $class = VendorClass::where('id', $collection->vendor_class_id)->first();

        $payment =  $order;

        if ($type) {
            $emailTemplates = "mail.liveclass.live_class_payment";
        }else{
            $emailTemplates = "mail.liveclass.live_class_onboard";
        }
        return view($emailTemplates, compact('data', 'payment', 'class', 'collection_type_name'));
        return $order;

        $this->sendNotification($order, PaymentStatus::Received);
        return response(['status' => true]);
    }

    public function sendNotificationToUser($id, $isAttached = null)
    {
        if ($isAttached ) {
            $order = VendorClass::where('id', $id)->latest()->first();
        }else{
            $order = CollectionOrder::where('id', $id)->latest()->first();
        }
        $this->sendNotification($order, PaymentStatus::Received, $isAttached);
        return response(['status' => true]);
        // return true;
    }

    public function sendAttachedNotificationToUser(Request $request)
    {
        $studentId = $request->purchaser_id;
        $classId = $request->class_id;
        $vendorId = $request->vendor_id;
        SendAttachedNotification::dispatch($classId , $studentId, $vendorId);
        return response(['status' => true]);
        // return true;
    }

    /**
     * Create a new product instance with all the relations.
     *
     * @param  array  $data
     * @return \App\Product
     */
    public function saveProductDetails(Request $request)
    {
        $user = auth()->user();
        // if (!$user->can('create', Product::class)) {
        //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
        // }
        $slugHelper = new SlugHelper();

        $slug = $slugHelper->slugify($request->name);
        $slugs = Product::where('slug', $slug);
        if ($request->id) {
            $slugs = $slugs->where('id', '!=', $request->id);
        }
        $slugs = $slugs->first();
        if ($slugs) {
            return response(['errors' =>  ['alreadyExist' => ['Product already exist with same name.']], 'status' => false, 'message' => ''], 422);
        }

        $data = [
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'sku' =>  $request->sku,
            'stock' =>  $request->stock,
            'merchant_code' =>  $request->merchant_code,
            'status' => $request->status,
        ];

        $tags = [];
        if (isset($request->tags) && $request->tags) {
            $data['tags'] = json_encode(
                array_map(function ($tag) {
                    return $tag['id'];
                }, $request->tags)
            );
        }
        if (isset($request->categories) && $request->categories) {
            $data['categories'] = json_encode(
                array_map(function ($category) {
                    return $category['id'];
                }, $request->categories)
            );
        }
        // if (isset($request->collections) && $request->collections) {
        //     $data['collection_id'] = array_map(function ($data) {
        //         return $data['id'];
        //     }, $request->collections);

        // }

        if (isset($request->collections) && $request->collections) {
            $data['collection_id'] = $request->collections[0]['id'];
        }

        $notFound = false;
        if ($request->id) {
            $data['updated_by'] = $user->id;
            $product = Product::find($request->id);
            if (! $product) {
                $notFound = true;
            }
            $product->update($data);
        }

        if (! $request->id && ! $notFound) {
            $data['created_by'] = $user->id;
            $product = Product::create($data);
        }

        return $product;
    }

    public function updateDiscountStatus(Request $request, $id)
    {
        $user = auth()->user();

        $discount = Discount::find($id);

        if (! $discount) {
            return response(['errors' =>  ['notFound' => ['discount not found.']], 'status' => false, 'message' => ''], 422);
        }
        if ($request->status) {
            $data = [
                'status'  => $request->status,
            ];

            $discount->update($data);
        }
    }

    public function updateProductStatus(Request $request, $id)
    {
        $user = auth()->user();

        $product = Product::find($id);

        if (! $product) {
            return response(['errors' =>  ['notFound' => ['Product not found.']], 'status' => false, 'message' => ''], 422);
        }
        if ($request->status) {
            $data = [
                'status'  => $request->status,
            ];

            $product->update($data);
        }
    }

    public function updatePriceStatus(Request $request, $id)
    {
        $user = auth()->user();

        $productPrice = ProductPrice::find($id);

        if (! $productPrice) {
            return response(['errors' =>  ['notFound' => ['Product price not found.']], 'status' => false, 'message' => ''], 422);
        }
        if ($request->status) {
            $data = [
                'status'  => $request->status,
            ];

            $productPrice->update($data);
        }
    }

    /**
     * Get details of a products.
     *
     * @param  int $id
     * @return \App\Product $product
     */
    public function getProductDetails($id)
    {
        $user = auth()->user();

        $product = Product::find($id);

        if (! $product) {
            return response(['errors' =>  ['notFound' => ['Product not found.']], 'status' => false, 'message' => ''], 422);
        }

        // if (!$user->can('view', $product)) {
        //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
        // }

        return new ProductResource($product);
        // return $product;
    }

    public function deleteProduct(Request $request)
    {
        $user = auth()->user();
        $productIds = $request->collectionIds;
        foreach ($productIds as $id) {
            $product = Product::find($id);
            if (! $product) {
                return response(['errors' =>  ['Product not Found'], 'status' => false, 'message' => ''], 422);
            }

            $product->delete();
        }

        return response(['message' =>  'Product deleted successfully', 'status' => false], 200);
    }

    public function deleteDiscount(Request $request)
    {
        $user = auth()->user();
        $discountIds = $request->collectionIds;
        foreach ($discountIds as $id) {
            $discount = Discount::find($id);
            if (! $discount) {
                return response(['errors' =>  ['Discount not Found'], 'status' => false, 'message' => ''], 422);
            }

            $discount->delete();
        }

        return response(['message' =>  'Discount deleted successfully', 'status' => false], 200);
    }

    public function deletePrice(Request $request)
    {
        $user = auth()->user();
        $PriceIds = $request->collectionIds;
        foreach ($PriceIds as $id) {
            $price = ProductPrice::find($id);
            if (! $price) {
                return response(['errors' =>  ['Price not Found'], 'status' => false, 'message' => ''], 422);
            }

            $price->delete();
        }

        return response(['message' =>  'Price deleted successfully', 'status' => false], 200);
    }

    public function restorePrice(Request $request)
    {
        $user = auth()->user();
        $priceIds = $request->collectionIds;
        foreach ($priceIds as $id) {
            $price = ProductPrice::withTrashed()->find($id);
            if (! $price) {
                return response(['errors' =>  ['Price not Found'], 'status' => false, 'message' => ''], 422);
            }
            $price->restore();
        }

        return response(['message' =>  'Price restored successfully', 'status' => false], 200);
    }

    public function restoreProduct(Request $request)
    {
        $user = auth()->user();
        $productIds = $request->collectionIds;
        foreach ($productIds as $id) {
            $product = Product::withTrashed()->find($id);
            if (! $product) {
                return response(['errors' =>  ['Product not Found'], 'status' => false, 'message' => ''], 422);
            }
            $product->restore();
        }

        return response(['message' =>  'Product restored successfully', 'status' => false], 200);
    }

    public function restoreDiscount(Request $request)
    {
        $user = auth()->user();
        $discountIds = $request->collectionIds;
        foreach ($discountIds as $id) {
            $discount = Discount::withTrashed()->find($id);
            if (! $discount) {
                return response(['errors' =>  ['Discount not Found'], 'status' => false, 'message' => ''], 422);
            }
            $discount->restore();
        }

        return response(['message' =>  'Discount restored successfully', 'status' => false], 200);
    }

    /**
     * Get details of all prices for a product.
     *
     * @param  int $id
     * @return \App\ProductPrice $prices
     */
    public function getProductPrices($id)
    {
    }

    /**
     * Get a list of all product prices registered with pagination.
     *
     * @param  Request $request
     * @return \App\ProductPrice $prices
     */
    public function getAllProductPrices(Request $request)
    {
        $user = auth()->user();

        $productPrice = ProductPrice::with('products', 'discounts')->latest();

        $vendorId = $request->vendor_id;
        $collectionType = $request->collection_type;

        if ($vendorId) {
            $productPrice = $productPrice->where('vendor_id', $vendorId);
        } else {
            $productPrice = $productPrice->whereNull('vendor_id');
        }

        if ($collectionType and in_array($collectionType, [CollectionType::classes, CollectionType::classDeck])) {
            $productPrice = $productPrice->whereNotNull('vendor_package_id');
        }

        if ($request->isTrashed) {
            $productPrice = $productPrice->onlyTrashed();
        }

        if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
            $productPrice = $productPrice->where('created_by', $user->id);
        }

        if ($request->search) {
            $productPrice = $productPrice
                ->where('color', 'like', "%{$request->search}%");
        }
        if ($request->min_price) {
            $productPrice = $productPrice
                ->where('price', '>=', $request->min_price);
        }
        if ($request->max_price) {
            $productPrice = $productPrice
                ->where('price', '<=', $request->max_price);
        }

        if ($request->maxRows) {
            $productPrice = $productPrice->paginate($request->maxRows);
        } else {
            $productPrice = $productPrice->get();
        }

        if ($request->maxRows) {
            $productPrice->getCollection()->transform(function ($data) {
                return $this->attachDiscountInCollection($data);
            });
        } else {
            $productPrice->map(function ($data) {
                return $this->attachDiscountInCollection($data);
            });
        }

        return $productPrice;
    }

    private function attachDiscountInCollection($data)
    {
        $discount_amount = null;
        if ($data->discounts) {
            $discount_amount = $data->discounts->amount;
            if ($data->discounts->is_percentage) {
                $discount_amount = $discount_amount.'%';
            }
        }
        $data['discount_amount'] = $discount_amount;

        return $data;
    }

    /**
     * Get details of all prices for a product.
     *
     * @param  int $id
     * @return \App\ProductPrice $price
     */
    public function getProductPriceDetails($id)
    {
        $user = auth()->user();

        $price = ProductPrice::with('medias')->find($id);

        $medias = $price->medias;
        if ($medias && count($medias) > 0) {
            $price->featured_image = $medias[0];
            $price->featured_image->url = Storage::disk('s3')->url($price->featured_image->url);
        }
        if (! $price) {
            return response(['errors' =>  ['notFound' => ['Product price not found.']], 'status' => false, 'message' => ''], 422);
        }
        unset($price['medias']);
        $price['products'] = Product::find($price->product_id);
        $price['discounts'] = Discount::find($price->discount_id);

        // if (!$user->can('viewAny', Product::class)) {
        //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
        // }

        return $price;
    }

    /**
     * Create a new product price instance.
     *
     * @param  request  $data
     * @return \App\ProductPrice
     */
    public function saveProductPrice(Request $request)
    {
        $user = auth()->user();
        if (! $request->price) {
            return response(['errors' =>  [['Amount Filed is required']], 'status' => false, 'message' => ''], 422);
        }
        if ($request->discounts) {
            $discount = $request->discounts;
            if ($discount['is_percentage'] && $discount['amount'] >= 100) {
                return response(['errors' =>  [['Discount is not valid']], 'status' => false, 'message' => ''], 422);
            }
            if (Carbon::parse($discount['end_date'])->endOfDay()->isPast()) {
                return response(['errors' =>  [['Discount end date must be greater than today.']], 'status' => false, 'message' => ''], 422);
            }
            if ($discount['amount'] >= $request->price) {
                return response(['errors' =>  [['Discounted amount should not be greater than or equal to actual price.']], 'status' => false, 'message' => ''], 422);
            }
        }
        // $vendor = $request->vendor;
        // $vendorId =
        // if (condition) {

        // }
        // if ($request->products) {
        //     $id = $request->products['id'];
        //     $product = Product::with('collection')->find($id);
        //     if ($product && $product->collection) {
        //         if ($product->collection->collection_type == CollectionType::classes || $product->collection->collection_type == CollectionType::liveClass) {
        //             $duplicatePrice = ProductPrice::where('product_id', $id)->when($request->id, function ($q) use ($request) {
        //                 return $q->where('id', '!=', $request->id);
        //             })->first();
        //             if ($duplicatePrice) {
        //                 return response(['errors' =>  [["Duplicate Price found for following live class."]], 'status' => false, 'message' => ''], 422);
        //             }
        //         }
        //     }
        // }

        $data = [
            'name' => $request->name,
            'color' => $request->color ? $request->color : null,
            'price' => $request->price ? $request->price : null,
            'unit' => $request->unit ? $request->unit : null,
            'sequence' =>  $request->sequence ? $request->sequence : null,
            'status' => $request->status ? $request->status : CouponStatus::Published,
        ];

        $data['product_id'] = ($request->products) ? $request->products['id'] : null;
        $data['discount_id'] = ($request->discounts) ? $request->discounts['id'] : null;
        $updateColPrice = true;
        if ($request->id) {
            $data['updated_by'] = $user->id;
            $productPrice = ProductPrice::find($request->id);
            $data['product_id'] = ($request->products) ? $request->products['id'] : $productPrice->product_id;
            $updateColPrice = false;

            $vendorPackage = CollectionHelper::createUpdatePackage($request, $user, $productPrice);
            $data['vendor_id'] = $vendorPackage->vendor_id;
            $data['vendor_package_id'] = $vendorPackage->id;

            // Detaching the published price of older collection if it is changed.
            if ($productPrice->product_id && $productPrice->product_id != $data['product_id']) {
                $product = Product::find($productPrice->product_id);
                $olderCollection = Collection::find($product->collection_id);
                $olderCollection->update(['published_price' => null]);
                $updateColPrice = true;
            }

            $productPrice->update($data);
        } else {
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;
            $vendorPackage = CollectionHelper::createUpdatePackage($request, $user);
            $data['vendor_id'] = $vendorPackage->vendor_id;
            $data['vendor_package_id'] = $vendorPackage->id;
            $productPrice = ProductPrice::create($data);
        }

        // Updating published price in new attached collection.
        if ($request->products && $updateColPrice) {
            $id = $request->products['id'];
            $newProd = Product::find($id);
            if ($newProd) {
                $class = Collection::find($newProd->collection_id);
                if ($class && $class->vendor_id) {
                    // $sa = new SubscriptionAdapter($class->vendor_id);
                    // $amountData = $sa->process($productPrice->price, $class->collection_type, null, false);
                    $subscription_included = false;
                    $amountData = TaxFeeHelper::getTaxCalculationData($class->vendor_id, $productPrice->price, $class->collection_type, null, $subscription_included);
                    $newProd->collection->update(['published_price' => $amountData['amount']]);
                }
            }
        }

        $productPrice->mediables()->delete();
        if (isset($request->featured_image) && $request->featured_image) {
            $productPrice->mediables()->create([
                'media_id' => $request->featured_image['id'],
                'name' => $request->featured_image['name'],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
        }

        return $productPrice;
    }

    public function attachProductPrice(Request $request, $type)
    {
        $productPriceId = $request->product_price_id;
        $productId = $request->product_id;

        $action_array = ['attach', 'detach'];
        if (! in_array($type, $action_array)) {
            return response(['errors' => ['methods' => ['not valid request']], 'status' => false, 'message' => ''], 422);
        }

        if (! $productPriceId) {
            return response(['errors' =>  ['product_price' => ['Product price required']], 'status' => false, 'message' => ''], 422);
        }

        if (! $productId) {
            return response(['errors' =>  ['product' => ['Product required']], 'status' => false, 'message' => ''], 422);
        }

        $product = Product::where('id', $productId)->first();
        $productPrice = ProductPrice::where('id', $productPriceId)->first();

        if (! $product || ! $productPrice) {
            return response(['errors' =>  ['product' => ['In valid request']], 'status' => false, 'message' => ''], 422);
        }

        if (! $product->collection_id) {
            return response(['errors' =>  ['product' => ['no collection attached']], 'status' => false, 'message' => ''], 422);
        }

        $collection = Collection::where('id', $product->collection_id)->first();

        if (! $collection) {
            return response(['errors' =>  ['product' => ['no collection collection']], 'status' => false, 'message' => ''], 422);
        }
        $status = true;
        if (in_array($collection->collection_type, [CollectionType::classes, CollectionType::classDeck])) {
            $attachData = CollectionHelper::attachPackageApiCall($productPrice->vendor_package_id, $collection->vendor_class_id, $type);

            return response($attachData['response'], $attachData['code']);

            if (! $status) {
                return response(['errors' => ['product' => ['server error']], 'status' => false, 'message' => ''], 422);
            }
        } else {
            CollectionHelper::attachDetachPrice($product, $productPrice, $type);
            $amount = null;
            if ($type == 'attach') {
                $subscription_included = false;
                $amountData = TaxFeeHelper::getTaxCalculationData($collection->vendor_id, $productPrice->price, $collection->collection_type, null, $subscription_included);
                $amount = $amountData['amount'];
            }
            $collection->update(['published_price' => $amount]);

            return response(['status' => true]);
        }
    }

    public function attachProductDiscount(Request $request, $type)
    {
        $productPriceId = $request->product_price_id;
        $discountId = $request->discount_id;

        $action_array = ['attach', 'detach'];
        if (! in_array($type, $action_array)) {
            return response(['errors' => ['methods' => ['not valid request']], 'status' => false, 'message' => ''], 422);
        }

        if (! $productPriceId) {
            return response(['errors' =>  ['product_price' => ['Product price required']], 'status' => false, 'message' => ''], 422);
        }

        if (! $discountId) {
            return response(['errors' =>  ['product' => ['Discount required']], 'status' => false, 'message' => ''], 422);
        }

        $discount = Discount::where('id', $discountId)->first();
        $productPrice = ProductPrice::where('id', $productPriceId)->first();

        if (! $discount || ! $productPrice) {
            return response(['errors' =>  ['product' => ['not a valid request']], 'status' => false, 'message' => ''], 422);
        }

        if ($productPrice->vendor_id != $discount->vendor_id) {
            return response(['errors' =>  ['product' => ['not a valid request']], 'status' => false, 'message' => ''], 422);
        }

        if ($productPrice->vendor_package_id) {
            $attachData = CollectionHelper::attachDiscountApiCall($productPrice->vendor_package_id, $discount->vendor_discount_id, $type);

            return response($attachData['response'], $attachData['code']);
        }

        if ($type == 'attach') {
            $productPrice->update(['discount_id' => $discount->id]);
        } else {
            $productPrice->update(['discount_id' => null]);
        }

        return response(['status' => true]);
    }

    /**
     * Get details of all discounts for a product.
     *
     * @param  int $id
     * @return \App\Discount $discounts
     */
    public function getProductDiscounts($id)
    {
        $user = auth()->user();

        $discounts = Discount::whereHas('product_prices', function ($query) use ($id) {
        })->get();

        if (! $discounts) {
            return response(['errors' =>  ['notFound' => ['Discounts not found.']], 'status' => false, 'message' => ''], 422);
        }

        if (! $user->can('viewAny', Product::class)) {
            return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
        }

        return $discounts;
    }

    /**
     * Get a list of all discounts registered.
     *
     * @param  Request $request
     * @return \App\Discount $discounts
     */
    public function getAllProductDiscounts(Request $request)
    {
        $user = auth()->user();
        $discounts = Discount::latest();

        $vendorId = $request->vendor_id;
        if ($vendorId) {
            $discounts = $discounts->where('vendor_id', $vendorId);
        } else {
            $discounts = $discounts->whereNull('vendor_id');
        }

        if ($request->isTrashed) {
            $discounts = $discounts->onlyTrashed();
        }

        if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
            $discounts = $discounts->where('created_by', $user->id);
        }

        // $discounts = $discounts->where('end_date', '>', $currentTime);

        if ($request->search) {
            $discounts = $discounts
                ->where('name', 'like', "%{$request->search}%");
        }

        if ($request->maxRows) {
            $discounts = $discounts->paginate($request->maxRows);
        } else {
            $discounts = $discounts->get();
        }

        return $discounts;
    }

    /**
     * Get details of all discounts for a product.
     *
     * @param  int $id
     * @return \App\Discount $discounts
     */
    public function getProductDiscountDetails($id)
    {
        $user = auth()->user();

        $discount = Discount::with('medias')->find($id);

        if (! $discount) {
            return response(['errors' =>  ['notFound' => ['Discount not found.']], 'status' => false, 'message' => ''], 422);
        }
        $medias = $discount->medias;
        if ($medias && count($medias) > 0) {
            $discount->featured_image = $medias[0];
            $discount->featured_image->url = Storage::disk('s3')->url($discount->featured_image->url);
        }
        // $discount = $discount->unsetRelation($medias);
        // $discount->unset($medias);
        unset($discount['medias']);

        // if (!$user->can('viewAny', Product::class)) {
        //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
        // }

        return $discount;
    }

    /**
     * Create a new product discount instance.
     *
     * @param  request  $data
     * @return \App\Discount
     */
    public function saveProductDiscount(Request $request)
    {
        $user = auth()->user();

        if ($request->is_percentage && $request->amount >= 100) {
            return response(['errors' =>  [['Discount percentage is not valid']], 'status' => false, 'message' => ''], 422);
        }
        if ($request->amount && $request->amount < 1) {
            return response(['errors' =>  [['Discount amount/percentage must be greater than zero.']], 'status' => false, 'message' => ''], 422);
        }

        $data = [
            'name'                      => $request->name,
            'code'                      => $request->code,
            'description'               => $request->description,
            'amount'                    => $request->amount,
            'is_percentage'             => $request->is_percentage ? $request->is_percentage : false,
            'max_count'                 => $request->max_count,
            'start_date'                => $request->start_date,
            'end_date'                  => $request->end_date,
            'status'                    => $request->status,
            'additional_threshold'      => $request->additional_threshold,
            'additional_amount'         => $request->additional_amount,
        ];

        $notFound = false;

        if ($request->id) {
            $data['updated_by'] = $user->id;
            $discount = Discount::find($request->id);
            $vendorDiscount = CollectionHelper::createUpdateDiscount($request, $user, $discount); // creating updating vendor discount
            $data['vendor_discount_id'] = $vendorDiscount->id;
            $data['vendor_id'] = $vendorDiscount->vendor_id;
            if ($discount) {
                $discount->update($data);
            } else {
                $notFound = true;
            }
        } else {
            $vendorDiscount = CollectionHelper::createUpdateDiscount($request, $user); // creating updating vendor discount
            $data['vendor_discount_id'] = $vendorDiscount->id;
            $data['vendor_id'] = $vendorDiscount->vendor_id;
        }

        if (! $request->id || $notFound) {
            $data['created_by'] = $user->id;
            $discount = Discount::create($data);
            if (isset($request->featured_image) && $request->featured_image) {
                $discount->mediables()->delete();
                $discount->mediables()->create([
                    'media_id' => $request->featured_image['id'],
                    'name' => $request->featured_image['name'],
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);
            }
        }

        return $discount;
    }

    /**
     * Get details of all orders for a product.
     *
     * @param  int $id
     * @return \App\Order $orders
     */
    public function getProductOrders($id)
    {
        $user = auth()->user();

        $order = Order::where('product_id', $id)->first();

        // if (!$order) {
        //     return response(['errors' =>  ['notFound' => ["order not found."]], 'status' => false, 'message' => ''], 422);
        // }

        // if (!$user->can('viewAny', Product::class)) {
        //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
        // }

        return $order;
    }

    /**
     * Get a list of all orders registered.
     *
     * @param  Request $request
     * @return \App\Order $Orders
     */
    public function getAllProductOrders(Request $request)
    {
        $user = auth()->user();
        $orders = Order::with('product', 'collection')->latest();
        if ($request->product_id) {
            $orders = $orders
                ->where('product_id', $request->product_id);
        }

        if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
            $orders = $orders
                ->whereHas('product', function ($query) use ($request, $user) {
                    $query->where('products.created_by', $user->id);
                });
        }

        if ($request->search) {
            $orders = $orders
                ->where('code', 'like', $request->search);
        }
        if ($request->start_date) {
            $orders = $orders->whereDate('created_at', '>=', Carbon::parse($request->start_date)->format('Y-m-d'));
            // $orders =  $orders->where('created_at', '>=',date($request->start_date)->toDateString());
            // $orders =  $orders->where('created_at', '>=', Carbon::parse($request->start_date)->format('m-d-y'));
        }

        if ($request->end_date) {
            $orders = $orders->whereDate('created_at', '<=', Carbon::parse($request->end_date)->format('Y-m-d'));
        }
        if ($request->maxRows) {
            $orders = $orders->paginate($request->maxRows);
        } else {
            $orders = $orders->get();
        }

        return $orders;
    }

    /**
     * Get details of all orders for a product.
     *
     * @param  int $id
     * @return \App\Order $orders
     */
    public function getProductOrderDetails($id)
    {
        $user = auth()->user();

        $order = Order::with('product')->find($id);

        if (! $order) {
            return response(['errors' =>  ['notFound' => ['Order not found.']], 'status' => false, 'message' => ''], 422);
        }

        // if (!$user->can('viewAny', Product::class)) {
        //     return response(['errors' => ['authError' => ["User is not authorized for this action"]], 'status' => false, 'message' => ''], 422);
        // }

        return $order;
    }

    /**
     * Get details of all reviews for a product.
     *
     * @param  int $id
     * @return \App\ProductReviews $orders
     */
    public function getProductReviews($id)
    {
        $reviews = ProductReviews::where('product_id', $id)->latest()->get();

        return $reviews;
    }

    /**
     * Validating coupon code.
     * @param Request
     * @return json
     */
    public function validateCouponCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'           => 'required',
            'event_slug'     => 'required',
            'product_id'     => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 400);
        }

        $isMobileClient = $request->mobile;
        $currentTime = Carbon::now();
        // $discount = Coupon::where('code', $request->code)
        //     ->where('start_date', '<', $currentTime)
        //     ->where('end_date', '>', $currentTime)
        //     ->where('status', CouponStatus::Published)
        //     ->whereHas('products', function ($query) use ($request) {
        //         $query->where('products.id', $request->product_id);
        //     })->first();

        $discount = Coupon::where('code', $request->code)
            ->where('start_date', '<', $currentTime)
            ->where('end_date', '>', $currentTime)
            ->where('status', CouponStatus::Published)
            ->first();

        if ($discount && $discount->vendor_id) {
            $product = Product::find($request->product_id);

            $collection = Collection::find($product->collection_id);

            if ($collection->vendor_id != $discount->vendor_id) {
                return response(['errors' => ['coupon' => ['Coupon expired or not valid, try another one']], 'status' => false, 'message' => ''], 422);
            }
        }

        if ($isMobileClient and ! $discount) {
            return response(['errors' => ['coupon' => ['Coupon expired or not valid, try another one']], 'status' => false, 'message' => ''], 422);
        }
        $response = ['status' => false, 'message' => 'Please enter valid coupon code', 'data' => null];
        if ($discount) {
            $response = ['status' => false, 'message' => 'Maximum limit of code exceed', 'data' => null];

            if (isset($request->total_amount) && $request->total_amount && ! $isMobileClient) {
                if ($discount->is_percentage && $discount->amount >= 100) {
                    $response = ['data' => null, 'status' => false, 'message' => 'Invalid coupon amount'];

                    return $response;
                } elseif (! $discount->is_percentage && ! ($discount->amount < $request->total_amount)) {
                    $response = ['data' => null, 'status' => false, 'message' => 'Coupon is not valid for given data.'];

                    return $response;
                }
            }

            $orderCount = Order::where('discount_id', $discount->id)
                // ->where('payment_status', PaymentStatus::Pending)
                ->where('payment_status', PaymentStatus::Received)
                ->count();

            if (! $discount->max_count) {
                $response['status'] = true;
                $response['message'] = 'Coupon code applied successfully!';
                $response['amount'] = $discount->amount;
                $response['is_percentage'] = $discount->is_percentage;
                $response['is_exclusive'] = $discount->is_exclusive;
                $response['discount_id'] = $discount->id;

                return $response;
            }

            if ($discount->max_count and $orderCount < $discount->max_count) {
                $response['status'] = true;
                $response['message'] = 'Coupon code applied successfully!';
                $response['amount'] = $discount->amount;
                $response['is_percentage'] = $discount->is_percentage;
                $response['is_exclusive'] = $discount->is_exclusive;
                $response['discount_id'] = $discount->id;

                return $response;
            }
        }

        if ($isMobileClient) {
            return response(['errors' => ['coupon' => ['Maximum limit of code exceed']], 'status' => false, 'message' => ''], 422);
        }

        return $response;
    }

    /**
     * Validating order details.
     * @param Request
     * @return json object
     */
    public function validatePayment(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'attendees'             => 'required',
            'attendees_info'        => 'required',

            // 'product_id'            => 'required',
            'purchaser_id'          => 'required',
            'product_price_id'      => 'required',
            // '*.email'           => 'required|email|max:255|regex:^[_A-Za-z0-9-\\+]+(\\.[_A-Za-z0-9-]+)*@[A-Za-z0-9-]+(\\.[A-Za-z0-9]+)*(\\.[A-Za-z]{2,})$^',
            'final_total_amount'    => 'required',
            'collection_id'         => 'required',
        ]);

        $platform = $request->platform ? $request->platform : PlatformType::Web;

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 400);
        }

        $collection = Collection::where('id', $request->collection_id)->first();
        $response = ['status' => false, 'message' => 'Unauthenticated user', 'data' => null];

        if (! $collection) {
            if ($platform > PlatformType::Web) {
                return response(['errors' => ['message' => ['collection not found']], 'status' => false, 'message' => ''], 422);
            }
            $response['message'] = 'collection not found';
            return $response;
        }

        // $user = UserHelper::validateUser($request->purchaser_id, $request->bearerToken());
        $user = auth()->user();
        if ($user == null) {
            if ($platform > PlatformType::Web) {
                return response(['errors' => ['message' => ['Unauthenticated user']], 'status' => false, 'message' => ''], 422);
            }

            return $response;
        }
        $code = CodeHelper::orderCode($request->purchaser_id);
        // $product_id = $request->product_id;

        // $productPrice = ProductPrice::where('id', $request->product_price_id)
        //     ->whereHas('products', function ($query) use ($product_id) {
        //         $query->where('id', $product_id);
        //     })->with('products', 'discounts')->first();

        $productPrice = CollectionHelper::getActivePackage($collection);

        $response['message'] = 'server error';
        if ($productPrice) {
            $request->published_amount = $collection->published_price;
            if ($platform > PlatformType::Web) {
                $totalAmount = $this->calculateEffectivePriceMobileClient($productPrice, $request, $collection->published_price);
            } else {
                $totalAmount = $this->calculateEffectivePrice($productPrice, $request, $collection->published_price);
            }

            $calculateAmount = $this->calculateAmountWithTax($totalAmount, $collection);

            if ($calculateAmount != $request->final_total_amount) {
                if ($platform > PlatformType::Web) {
                    return response(['calculateAmount' =>  $calculateAmount, 'totalAmount' => $totalAmount, 'errors' => ['message' => ['server error try again']], 'status' => false, 'message' => ''], 422);
                }

                return $response;
            }

            if ($platform > PlatformType::Web) {
                $attendeesData = json_decode($request->attendees_info)->attendees;
            } else {
                $attendeesData = $request->attendees_info;
            }

            $calculateTaxData = $this->calculateAmountWithTax($totalAmount, $collection, false);

            $jsonData = [
                'attendees'        => $attendeesData,
                'product_price_id' => $productPrice->id,
                'productPrice'     => $productPrice,
                'vendor_id'        => null,
                'coupon'           => null,
                'collection_id'    => null,
                'collection_type'  => null,
                'created_by'       => $user->id,
                'tax_data'         => $calculateTaxData,
                'product_amount'   => $collection->published_price,
            ];


            $collection_id = null;

            $jsonData['vendor_id'] = $collection->vendor_id;
            $jsonData['collection_type'] = $collection->collection_type;
            $jsonData['collection_id'] =  $collection_id = $collection->id;

            //if coupon is applied
            if ($request->discount_coupon_id) {
                $currentTime = Carbon::now()->format('Y-m-d');
                $coupon = Coupon::whereDate('start_date', '<=', $currentTime)
                    ->whereDate('end_date', '>=', $currentTime)
                    ->where('status', CouponStatus::Published)
                    ->where('id', $request->discount_coupon_id)->first();
                if ($coupon) {
                    $jsonData['coupon'] = $coupon;
                }
            }

            $data = [
                'code'              => $code,
                'amount'            => $calculateAmount,
                'product_id'        => $request->product_id,
                'collection_id'     => $collection_id,
                'order_note'        => "order #$code",
                'purchaser_id'      => $request->purchaser_id,
                'created_by'        => $user->id,
                'discount_id'       => $request->discount_coupon_id,
                'meta'              => json_encode($jsonData),
                'payment_status'    => PaymentStatus::Pending,
                'platform_type'     => $platform,
            ];

            $order = CollectionOrder::create($data);
            $phone = $user['phone'];
            $email = $user['email'];

            if ($user['role_id'] == VendorRoleType::Student) {
                $phone = substr($user['phone'], strpos($user['phone'], '-') + 1);
                $email = substr($user['email'], strpos($user['email'], '-') + 1);
            }

            $statusUrlBase = config('app.cash_free.returnUrl');
            if ($collection->collection_type == CollectionType::classDeck) {
                $statusUrlBase = config('app.cash_free.returnUrlLiveClasses');
            } elseif ($collection->collection_type == CollectionType::classes) {
                $statusUrlBase = config('app.cash_free.returnUrlClasses');
            }

            $returnUrl = $statusUrlBase.'?id='.encrypt($order->id);
            $notifyUrl = config('app.cash_free.notifyUrl').'?id='.encrypt($order->id);
            if ($platform > PlatformType::Web) {
                $returnUrl = URL::signedRoute('mobilePaymentStatus', ['id' => encrypt($order->id), 'payid' => $code]);
            }

            $postData = [
                'appId'         =>  config('app.cash_free.app_id'),
                'orderId'       =>  $code,
                'orderAmount'   =>  $calculateAmount,
                'orderNote'     =>  $order->order_note,
                'customerName'  =>  $user['name'],
                'customerPhone' =>  $phone,
                'customerEmail' =>  $email,
                'returnUrl'     =>  $returnUrl,
                'notifyUrl'     =>  $notifyUrl,
            ];

            //To create signature for this order
            ksort($postData);
            $signatureData = '';
            foreach ($postData as $key => $value) {
                $signatureData .= $key.$value;
            }
            $signature = hash_hmac('sha256', $signatureData, config('app.cash_free.secret_key'), true);
            $signature = base64_encode($signature);
            $postData['signature'] = $signature;

            //To create unique Payment token for this order
            $appId = config('app.cash_free.app_id'); //replace it with your appId
            $secretKey = config('app.cash_free.secret_key'); //replace it with your secret key
            $orderId = $code; //$order->id; // change by vipin
            $orderAmount = $calculateAmount;
            // $returnUrl = config('app.cash_free.returnUrl');
            $paymentModes = ''; //keep it blank to display all supported modes
            $tokenData = 'appId='.$appId.'&orderId='.$orderId.'&orderAmount='.$orderAmount.'&returnUrl='.$returnUrl.'&paymentModes='.$paymentModes;
            $token = hash_hmac('sha256', $tokenData, $secretKey, true);
            $paymentToken = base64_encode($token);
            $postData['paymentToken'] = $paymentToken;

            //updating response
            $response['status'] = true;
            $response['message'] = 'Order initiated successfully';

            $response['data'] = $postData;
            //updating
            $order->update(['pg_request_data' => json_encode($response['data'])]);
            if ($platform > PlatformType::Web) {
                return [
                    'id'    => encrypt($order->id),
                    'payid' => $code,
                    'url'   => route('paymentflow', ['id' => $order->id, 'payid' => $code]),
                ];
            }
        }

        if ($platform > PlatformType::Web) {
            return response(['errors' => ['message' => ['Product not found']], 'status' => false, 'message' => ''], 422);
        }

        return $response;
    }

    /**
     * Validating order details.
     * @param Request
     * @return json object
     */
    public function validateFreePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendees'             => 'required',
            'attendees_info'        => 'required',
            'purchaser_id'          => 'required',
            'collection_id'         => 'required',
        ]);

        $platform = $request->platform ? $request->platform : PlatformType::Web;

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 400);
        }
        $response = ['status' => false, 'message' => 'Unauthenticated user', 'data' => null];

        $user = auth()->user();
        if ($user == null) {
            if ($platform > PlatformType::Web) {
                return response(['errors' => ['message' => ['Unauthenticated user']], 'status' => false, 'message' => ''], 422);
            }

            return $response;
        }

        // $alreadyOrdered = Order::where('purchaser_id', $request->purchaser_id)
        // ->where('collection_id', $request->collection_id)
        // ->where('payment_status', PaymentStatus::Received)
        // ->first();
        // if ($alreadyOrdered) {
        //     return response(['errors' => ['message' => ["Already enrolled in the class"]], 'status' => false, 'message' => ''], 422);
        // }

        $code = CodeHelper::orderCode($request->purchaser_id);

        $response['message'] = 'server error';
        $totalAmount = 0;

        $collection = \DB::connection('partner_mysql')->table('collections')->where('id', $request->collection_id)->first();

        // info($collection);
        // if ($collection->collection_type) {
        //     # code...
        // }
        if ($collection) {
            if ($platform > PlatformType::Web) {
                $attendeesData = json_decode($request->attendees_info)->attendees;
            } else {
                $attendeesData = $request->attendees_info;
            }

            $calculateTaxData = $this->calculateAmountWithTax(0, $collection, false);

            $jsonData = [
                'attendees'        => $attendeesData,
                'productPrice'     => 0,
                'vendor_id'        => $collection->vendor_id,
                'coupon'           => null,
                'collection_id'    => $collection->id,
                'collection_type'  => $collection->collection_type,
                'created_by'       => auth()->id(),
                'tax_data'         => $calculateTaxData,
                'product_amount'   => $collection->published_price,

            ];

            $product_id = null;

            $data = [
                'code'              => $code,
                'amount'            => $totalAmount,
                'product_id'        => $product_id,
                'collection_id'     => $request->collection_id,
                'order_note'        => "order #$code",
                'purchaser_id'      => $request->purchaser_id,
                'discount_id'       => $request->discount_coupon_id,
                'meta'              => json_encode($jsonData),
                'payment_status'    => PaymentStatus::Received,
                'platform_type'     => $platform,
                'created_by'        => $user->id,
            ];

            $order = CollectionOrder::create($data);
            $phone = $user['phone'];
            $email = $user['email'];

            if ($user['role_id'] == VendorRoleType::Student) {
                $phone = substr($user['phone'], strpos($user['phone'], '-') + 1);
                $email = substr($user['email'], strpos($user['email'], '-') + 1);
            }

            $postData = [
                'appId'         =>  config('app.cash_free.app_id'),
                'orderId'       =>  $code,
                'orderAmount'   =>  $totalAmount,
                'orderNote'     =>  $order->order_note,
                'customerName'  =>  $user['name'],
                'customerPhone' =>  $phone,
                'customerEmail' =>  $email,
                'returnUrl'     =>  '',
                'notifyUrl'     =>  '',
            ];

            $postData['paymentToken'] = '';

            //updating response
            $response['status'] = true;
            $response['message'] = 'Order initiated successfully';

            $response['data'] = $postData;
            //updating
            $order->update(['pg_request_data' => json_encode($response['data'])]);
            $this->registerFreeStudents($collection, PaymentStatus::Received, $request->purchaser_id);


                $this->sendNotification($order, PaymentStatus::Received);


            return response(['message' =>  'Order saved successfully', 'status' => false, 'id' => $order->id], 200);
        }

        return response(['errors' => ['message' => ['server error try again']], 'status' => false, 'message' => ''], 422);
    }

    /**
     * Calculate Effective Price for the payment.
     * @param \App\ProductReviews $productPrice, $request
     * @return number $totalAmount
     */
    private function calculateEffectivePrice($productPrice, $request, $productAmount)
    {
        // Loading basic discount for the event price
        // $productPrice->load('discounts');
        $discount = null;
        if ($productPrice->discounts and $productPrice->discounts->status == PublishStatus::Published) {
            $discount = $productPrice->discounts;
        }
        // Making variables for the different discount attributes
        $productAmount = $productAmount; // $request->published_amount;//$productPrice->price;
        $totalProductAmount = $productPrice->price * $request->attendees;
        // $discount = $productPrice->discounts;

        $validDiscount = $discount and (Carbon::parse($discount->start_date)->isPast() && ! Carbon::parse($discount->end_date)->endOfDay()->isPast()) ? true : false;

        $discountAmount = $discount && $validDiscount ? $discount->amount : 0;
        $isPercentageDiscount = $discount && $validDiscount && $discount->is_percentage ? $discount->is_percentage : false;
        $additionalDiscountThreshold = $discount && $validDiscount && $discount->additional_threshold ? $discount->additional_threshold : null;
        $additionalDiscountAmount = $discount && $validDiscount && $discount->additional_amount ? $discount->additional_amount : 0;

        // Finding appied Coupon and fetching its data
        $appliedCoupon = $request->discount_coupon_id ? $request->discount_coupon_id : null;
        // $couponCode                 = Coupon::find($appliedCoupon);
        $currentTime = Carbon::now()->format('Y-m-d');
        $couponCode = Coupon::whereDate('start_date', '<=', $currentTime)
            ->whereDate('end_date', '>=', $currentTime)
            ->where('status', CouponStatus::Published)
            ->where('id', $appliedCoupon)->first();
        $couponAmount = ($couponCode && $couponCode->amount) ? $couponCode->amount : 0;
        $isPercentageCoupon = ($couponCode && $couponCode->is_percentage) ? $couponCode->is_percentage : false;
        $exclusiveCoupon = ($couponCode && $couponCode->is_exclusive) ? $couponCode->is_exclusive : false;

        // Taking initial ammount as 0
        $totalAmount = 0;

        //If coupon is applied and coupon is exclusive(no other discount with the given coupon)
        if ($couponCode && $exclusiveCoupon) {
            // If coupon is exclusive and percentage off
            if ($isPercentageCoupon) {
                $couponAmount = $productAmount * $couponAmount / 100;
            }

            $totalAmount = $productAmount - $couponAmount;
            if ($totalAmount < 1) {
                return $productAmount * $request->attendee;
            }
            // $totalAmount = $productAmount - $couponAmount;

            return $totalAmount;
        }
        // If coupon is not applied or if coupon is not exclusive
        else {
            /**
             *    Discounted amount variable
             *    So that if inclusive coupon is applied
             *    Then coupon amount will be applied on discounted amount.
             */
            $discountEffectiveAmount = $productAmount;

            // Applying Basic Discount
            if ($discount) {
                // If Attendee count is greater than or equal to discount threshold
                if ($request->attendees >= $additionalDiscountThreshold) {
                    $discountAmount = $discountAmount + $additionalDiscountAmount;
                } else {
                    // when Attendee count is less than discount threshold, no additional discount
                    $discountAmount = $discountAmount;
                }

                if ($isPercentageDiscount) {
                    $discountAmount = ($discountAmount * $productAmount) / 100;
                }
                $discountEffectiveAmount = $productAmount - $discountAmount;
            }

            $totalAmount = $discountEffectiveAmount * $request->attendees;

            // Inclusive Coupons
            if ($couponCode && ! $exclusiveCoupon) {
                // If coupon applied is percentage off
                if ($isPercentageCoupon) {
                    $couponAmount = $totalAmount * $couponAmount / 100;
                }

                // Coupon amount should be less than total amount so that payable amount can't be zero or less.
                if ($couponAmount < $totalAmount) {
                    $totalAmount = $totalAmount - $couponAmount;
                }
            }

            return $totalAmount;

            // $totalAmount = $discountEffectiveAmount;
        }

        // return final price
        return $totalAmount;
    }

    /**
     * Calculate Effective Price for the payment mobile client.
     * @param \App\Order $productPrice, $request
     * @return number $totalAmount
     */
    private function calculateEffectivePriceMobileClient($productPrice, $request, $productAmount)
    {
        // Loading basic discount for the event price
        $discount = null;
        if ($productPrice->discounts and $productPrice->discounts->status == PublishStatus::Published) {
            $discount = $productPrice->discounts;
        }

        // Making variables for the different discount attributes
        $productAmount = $productAmount; //$request->published_amount;  //$productPrice->price;
        $totalProductAmount = $productPrice->price * $request->attendees;

        $discountAmount = $discount ? $discount->amount : 0;
        $isPercentageDiscount = $discount && $discount->is_percentage ? $discount->is_percentage : false;
        $additionalDiscountThreshold = $discount && $discount->additional_threshold ? $discount->additional_threshold : null;
        $additionalDiscountAmount = $discount && $discount->additional_amount ? $discount->additional_amount : 0;
        // $validDiscount                      = $discount  and (Carbon::parse($discount->start_date)->isPast() && !Carbon::parse($discount->end_date)->endOfDay()->isPast()) ? true : false;

        // Finding appied Coupon and fetching its data
        $appliedCoupon = $request->discount_coupon_id ? $request->discount_coupon_id : null;
        $couponCode = Coupon::find($appliedCoupon);
        $couponAmount = ($couponCode && $couponCode->amount) ? $couponCode->amount : 0;
        $isPercentageCoupon = ($couponCode && $couponCode->is_percentage) ? $couponCode->is_percentage : false;
        $exclusiveCoupon = ($couponCode && $couponCode->is_exclusive) ? $couponCode->is_exclusive : false;

        // Taking initial ammount as 0
        $totalAmount = 0;

        //If coupon is applied and coupon is exclusive(nho other discount with the given coupon)
        if ($couponCode && $exclusiveCoupon) {

            // If coupon is exclusive and percentage off
            if ($isPercentageCoupon) {
                $couponAmount = $productAmount * $couponAmount / 100;
            }

            $totalAmount = $productAmount - $couponAmount;
            if ($totalAmount < 1) {
                return $productAmount * $request->attendee;
            }

            return $totalAmount * $request->attendees;
        }
        // If coupon is not applied or if coupon is not exclusive
        else {

            /**
             *    Discounted amount variable
             *    So that if inclusive coupon is applied
             *    Then coupon amount will be applied on discounted amount.
             */
            $discountEffectiveAmount = $productAmount;

            // Applying Basic Discount
            if ($discount) {
                // If Attendee count is greater than or equal to discount threshold
                if ($request->attendees >= $additionalDiscountThreshold) {
                    // $discountAmount = $discountAmount + $additionalDiscountAmount;
                } else {
                    // when Attendee count is less than discount threshold, no additional discount
                    $discountAmount = $discountAmount;
                }
                if ($isPercentageDiscount) {
                    $discountAmount = ($discountAmount * $productAmount) / 100;
                }
                $discountEffectiveAmount = $productAmount - $discountAmount;
                if ($discountEffectiveAmount < 1) {
                    $discountEffectiveAmount = $productAmount;
                }
            }

            $totalAmount = $discountEffectiveAmount * $request->attendees;

            // Inclusive Coupons
            if ($couponCode && ! $exclusiveCoupon) {
                // If coupon applied is percentage off
                if ($isPercentageCoupon) {
                    $couponAmount = $totalAmount * $couponAmount / 100;
                }
                $totalAmount = $totalAmount - $couponAmount;
                if ($totalAmount < 1) {
                    $totalAmount = $discountEffectiveAmount * $request->attendees;
                }
            }

            return $totalAmount;

            // $totalAmount = $discountEffectiveAmount;
        }

        // return final price
        return $totalAmount;
    }

    public function paymentStatus($orderId)
    {
        $id = decrypt($orderId);
        $order = Order::find($id);

        if (! $order) {
            return 'cURL Error #:'.'order not found';
        }

        $orderCode = $order->code;
        $curl = curl_init();
        $appId = config('app.cash_free.app_id');
        $secretKey = config('app.cash_free.secret_key');

        curl_setopt_array($curl, [
            CURLOPT_URL => config('app.cash_free.CURLOPT_URL'),
            CURLOPT_RETURNTRANSFER => config('app.cash_free.CURLOPT_RETURNTRANSFER'),
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => config('app.cash_free.CURLOPT_MAXREDIRS'),
            CURLOPT_TIMEOUT => config('app.cash_free.CURLOPT_TIMEOUT'),
            CURLOPT_HTTP_VERSION => config('app.cash_free.CURLOPT_HTTP_VERSION'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "appId={$appId}&secretKey={$secretKey}&orderId={$orderCode}",
            // CURLOPT_POSTFIELDS => sprintf(config('app.cash_free.CURLOPT_POSTFIELDS'), $appId, $secretKey, $id),
            CURLOPT_HTTPHEADER => [
                'cache-control: no-cache',
                'content-type: application/x-www-form-urlencoded',
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        $jsonResponse = ['status' => false, 'message' => 'Server error', 'data' => 'cURL Error #:'.$err];
        if ($err) {
            return 'cURL Error #:'.$err;
        } else {

            // $order = Order::where('code', $orderId)->first();
            // 'status' => 'ERROR',&& $order->status == PaymentStatus::Pending
            $result = json_decode($response, true);

            // return collect( $result );
            if ($order) {
                if ($result['status'] == 'ERROR') {
                    $jsonResponse['message'] = $result['reason'];
                    $jsonResponse['status'] = false;
                } else {
                    $result = json_decode($response, true);
                    $atendeeNo = count(json_decode($order->meta)->attendees);
                    if ($order->payment_status == PaymentStatus::Pending) {
                        // UserHelper::updateEnthuPoints($order->purchaser_id, $atendeeNo, $request->bearerToken());
                    }

                    $status = PaymentStatus::Received;
                    if (! isset($result['txStatus'])) {
                        $status = PaymentStatus::Cancel;
                    }

                    if (isset($result['txStatus']) and $result['txStatus'] == 'FAILED') {
                        $status = PaymentStatus::Failed;
                    }

                    $order->transaction_id = isset($result['referenceId']) ? $result['referenceId'] : null;
                    $order->payment_mode = isset($result['paymentMode']) ? $result['paymentMode'] : null;
                    $order->transaction_date = isset($result['txTime']) ? $result['txTime'] : null;
                    $order->transaction_amount = isset($result['orderAmount']) ? $result['orderAmount'] : null;
                    $order->transaction_data = $response;
                    $order->payment_status = $status;

                    $order->save();
                    $jsonOrderMeta = json_decode($order->meta, true);
                    $result['attendees'] = $jsonOrderMeta['attendees'];
                    $jsonResponse['message'] = 'Successfully found result';
                    $jsonResponse['status'] = true;

                    $order->refresh();
                    $this->sendNotification($order, $status);
                    $this->registerStudents($order, $status);
                }
            }
            $jsonResponse['data'] = $result;

            return $jsonResponse;
        }
    }

    // mobile payment status
    public function paymentMobileClientStatus($orderId)
    {
        $id = decrypt($orderId);
        $order = Order::find($id);
        if (! $order) {
            return response(['errors' => ['payment' => ['Payment faild']], 'status' => false, 'message' => ''], 422);
        }

        $orderCode = $order->code;
        $curl = curl_init();
        $appId = config('app.cash_free.app_id');
        $secretKey = config('app.cash_free.secret_key');
        curl_setopt_array($curl, [
            CURLOPT_URL => config('app.cash_free.CURLOPT_URL'),
            CURLOPT_RETURNTRANSFER => config('app.cash_free.CURLOPT_RETURNTRANSFER'),
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => config('app.cash_free.CURLOPT_MAXREDIRS'),
            CURLOPT_TIMEOUT => config('app.cash_free.CURLOPT_TIMEOUT'),
            CURLOPT_HTTP_VERSION => config('app.cash_free.CURLOPT_HTTP_VERSION'),
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "appId={$appId}&secretKey={$secretKey}&orderId={$orderCode}",
            // CURLOPT_POSTFIELDS => sprintf(config('app.cash_free.CURLOPT_POSTFIELDS'), $appId, $secretKey, $id),
            CURLOPT_HTTPHEADER => [
                'cache-control: no-cache',
                'content-type: application/x-www-form-urlencoded',
            ],
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            $order->transaction_data = $response;
            $order->payment_status = PaymentStatus::Failed;
            $order->save();

            return response(['errors' => ['payment' => ['Server error']], 'status' => false, 'message' => ''], 422);
        } else {
            $status = PaymentStatus::Failed;
            $returnResponse = response(['status' => true, 'message' => 'success', 'order_id' => $id], 200);
            $jsonResponse = json_decode($response, true);

            if ($jsonResponse['status'] == 'OK') {
                if ($jsonResponse['orderStatus'] == 'ACTIVE') {
                    $status = PaymentStatus::Cancel;
                    $returnResponse = response(['errors' => ['payment' => ['Payment is canceled']], 'status' => false, 'message' => ''], 422);
                } else {
                    if ($jsonResponse['orderStatus'] == 'PAID') {
                        $status = PaymentStatus::Received;
                    } else {
                        $status = PaymentStatus::Processed;
                        $returnResponse = response(['errors' => ['payment' => ['Payment is under processing']], 'status' => false, 'message' => ''], 422);
                    }
                }
            } else {
                $status = PaymentStatus::Failed;
                $returnResponse = response(['errors' => ['payment' => ['Payment not done']], 'status' => false, 'message' => ''], 422);
            }

            $referenceId = null;
            $txTime = null;
            $orderAmount = null;
            $paymentMode = null;
            if (isset($jsonResponse['referenceId'])) {
                $referenceId = $jsonResponse['referenceId'];
            }
            if (isset($jsonResponse['txTime'])) {
                $referenceId = $jsonResponse['txTime'];
            }
            if (isset($jsonResponse['orderAmount'])) {
                $referenceId = $jsonResponse['orderAmount'];
            }
            if (isset($jsonResponse['paymentMode'])) {
                $referenceId = $jsonResponse['paymentMode'];
            }

            $order->transaction_id = $referenceId;
            $order->transaction_date = $txTime;
            $order->transaction_data = $response;
            $order->payment_status = $status;
            $order->transaction_amount = $orderAmount;
            $order->payment_mode = $paymentMode;
            $order->save();

            $order->refresh();
            $this->sendNotification($order, $status);
            $this->registerStudents($order, $status);

            return $returnResponse;
        }
    }

    public function updateOrderDetails(Request $request)
    {
        return $request;
    }

    public function saveProductCoupon(Request $request)
    {
        $user = auth()->user();

        if ($request->is_percentage && $request->amount >= 100) {
            return response(['errors' =>  [['Coupon percentage is not valid']], 'status' => false, 'message' => ''], 422);
        }
        if ($request->amount && $request->amount <= 0) {
            return response(['errors' =>  [['Coupon amount must be greater than zero.']], 'status' => false, 'message' => ''], 422);
        }

        // if (!$request->is_percentage && $request->products) {
        //     $productIds = array_map(function ($product) {
        //         return $product['id'];
        //     }, $request->products);
        //     $publishedProductPrice = ProductPrice::with('products')
        //         ->whereIn('id', $productIds)
        //         ->where('price', '<=', $request->amount)
        //         ->where('status', CouponStatus::Published)
        //         ->first();
        //     if($publishedProductPrice) {
        //         return response(['errors' =>  [["Coupon amount must be less than product price."]], 'status' => false, 'message' => ''], 422);
        //     }
        // }

        $data = [
            'name'                  => $request->name,
            'code'                  => $request->code,
            'description'           => $request->description,
            'amount'                => $request->amount,
            'is_percentage'         => $request->is_percentage ? $request->is_percentage : false,
            'is_exclusive'          => $request->is_exclusive ? $request->is_exclusive : false,
            'max_count'             => $request->max_count,
            'start_date'            => Carbon::parse($request->start_date)->startOfDay(),
            'end_date'              => Carbon::parse($request->end_date)->endOfDay(),
            'status'                => $request->status,
            'additional_threshold'  => $request->additional_threshold,
            'additional_amount'     => $request->additional_amount,
        ];

        $notFound = false;
        if ($request->id) {
            $coupon = Coupon::find($request->id);
            $vendorCoupon = CollectionHelper::createUpdateCoupon($request, $user, $coupon); // creating updating vendor coupon
            $data['vendor_coupon_id'] = $vendorCoupon->id;
            $data['vendor_id'] = $vendorCoupon->vendor_id;
            if (! $coupon) {
                $data['updated_by'] = $user->id;
                $notFound = true;
            }
            $coupon->update($data);
        } else {
            $vendorCoupon = CollectionHelper::createUpdateCoupon($request, $user); // creating updating vendor coupon
            $data['vendor_coupon_id'] = $vendorCoupon->id;
            $data['vendor_id'] = $vendorCoupon->vendor_id;
        }
        if (! $request->id && ! $notFound) {
            $data['created_by'] = $user->id;
            $coupon = Coupon::create($data);
        }
        // $productIds = [];
        if ($request->products) {
            $product_ids = array_map(function ($product) {
                return $product['id'];
            }, $request->products);
            $coupon->products()->sync($product_ids);
        } elseif ($request->id && ! $request->products) {
            $coupon->products()->sync([]);
        }

        return $coupon;
    }

    public function getAllProductCoupons(Request $request)
    {
        $user = auth()->user();
        $coupons = Coupon::latest();

        $vendorId = $request->vendor_id;
        if ($vendorId) {
            $coupons = $coupons->where('vendor_id', $vendorId);
        }

        if ($request->isTrashed) {
            $coupons = $coupons->onlyTrashed();
        }

        if ($request->search) {
            $coupons = $coupons
                ->where('name', 'like', "%{$request->search}%");
        }

        if ($user->role_id != UserRole::SuperAdmin && $user->role_id != UserRole::Approver) {
            $coupons = $coupons->where('created_by', $user->id);
        }

        if ($request->maxRows) {
            $coupons = $coupons->paginate($request->maxRows);
        } else {
            $coupons = $coupons->get();
        }

        return $coupons;
    }

    public function getProductCoupons($id)
    {
        $user = auth()->user();

        $coupon = Coupon::with('products')->find($id);

        if (! $coupon) {
            return response(['errors' =>  ['notFound' => ['Coupon not found.']], 'status' => false, 'message' => ''], 422);
        }

        if (! $user->can('viewAny', Coupon::class)) {
            return response(['errors' => ['authError' => ['User is not authorized for this action']], 'status' => false, 'message' => ''], 422);
        }

        return $coupon;
    }

    public function getProductCouponDetails($id)
    {
        $user = auth()->user();

        $coupon = Coupon::with('products')->find($id);

        if (! $coupon) {
            return response(['errors' =>  ['notFound' => ['Coupon not found.']], 'status' => false, 'message' => ''], 422);
        }

        return $coupon;
    }

    public function restoreCoupon(Request $request)
    {
        $user = auth()->user();
        $couponIds = $request->collectionIds;
        foreach ($couponIds as $id) {
            $coupon = Coupon::withTrashed()->find($id);
            if (! $coupon) {
                return response(['errors' =>  ['Coupon not Found'], 'status' => false, 'message' => ''], 422);
            }
            $coupon->restore();
        }

        return response(['message' =>  'Coupon restored successfully', 'status' => false], 200);
    }

    public function deleteCoupon(Request $request)
    {
        $user = auth()->user();
        $couponIds = $request->collectionIds;
        foreach ($couponIds as $id) {
            $coupon = Coupon::find($id);
            if (! $coupon) {
                return response(['errors' =>  ['Coupon not Found'], 'status' => false, 'message' => ''], 422);
            }
            // $coupon->products()->delete();
            $coupon->products()->detach();
            $coupon->delete();
        }

        return response(['message' =>  'Coupon deleted successfully', 'status' => false], 200);
    }

    public function updateCouponStatus(Request $request, $id)
    {
        $user = auth()->user();

        $coupon = Coupon::find($id);

        if (! $coupon) {
            return response(['errors' =>  ['notFound' => ['Coupon not found.']], 'status' => false, 'message' => ''], 422);
        }
        if ($request->status) {
            $data = [
                'status'  => $request->status,
            ];

            $coupon->update($data);
        }
    }

    private function sendNotification($order, $status, $isAttached = false)
    {
        if ($status == PaymentStatus::Received) {
            SendBookingNotification::dispatch($order, $isAttached);
        }
    }

    private function registerStudents($order, $status)
    {
        if ($status == PaymentStatus::Received) {
            $order->load('collection', 'purchaser', 'product');
            $collection = $order->collection;

            $collections = [CollectionType::classes, CollectionType::classDeck];

            if ($collection and $collection->vendor_id and in_array($collection->collection_type, $collections)) {
                $vendor = Vendor::find($collection->vendor_id);
                $product = $order->product;
                $student = Student::where('user_id', $order->purchaser_id)->first();
                if ($student) {
                    $package = Fee::where('id', $product->vendor_package_id)->first();
                    $studentRegistration = StudentRegistration::where('student_id', $student->id)->where('vendor_id', $vendor->id)->first();
                    $registrationCode = config('client.reg_code').now()->timestamp.''.$student->id;
                    if ($studentRegistration) {
                        $registrationCode = $studentRegistration->registration_code;
                    }

                    $offer = DB::connection('partner_mysql')->table('fee_discount')->where('package_id', $package->id ?? null)->where('isActive', 1)->first();
                    $class = DB::connection('partner_mysql')->table('vendor_classes')->where('id', $collection->vendor_class_id)->first();

                    $registrationData = [
                        'registration_code' => $registrationCode,
                        'student_id'        => $student->id,
                        'location_id'       => $class->location_id,
                        'vendor_id'         => $vendor->id,
                        'vendorclass_id'    => $class->id,
                        'fee_id'            => $package->id ?? null,
                        'discount_id'       => $offer ? $offer->id : null,
                        'coupon_id'         =>  null,
                        'remarks'           => '',
                        'start_date'        => now(),
                        'end_date'          => $class->end_date,
                        'created_by'        => auth()->id(),
                    ];

                    StudentRegistration::create($registrationData);
                }
            }
        }
    }

    private function registerFreeStudents($collection, $status, $purchaser_id)
    {
        if ($status == PaymentStatus::Received) {

            // $collections = [CollectionType::classes, CollectionType::liveClass];

            if ($collection and $collection->vendor_id) {
                $vendor = Vendor::find($collection->vendor_id);
                $student = Student::where('user_id', $purchaser_id)->first();
                if ($student) {

                    $studentRegistration = StudentRegistration::where('student_id', $student->id)->where('vendor_id', $vendor->id)->first();
                    $registrationCode = config('client.reg_code').now()->timestamp.''.$student->id;
                    if ($studentRegistration) {
                        $registrationCode = $studentRegistration->registration_code;
                    }

                    $class = DB::connection('partner_mysql')->table('vendor_classes')->where('id', $collection->vendor_class_id)->where('status', 1)->first();

                    $registrationData = [
                        'registration_code' => $registrationCode,
                        'student_id'        => $student->id,
                        'user_id'           => $student->user_id,
                        'location_id'       => $class->location_id,
                        'vendor_id'         => $vendor->id,
                        'vendorclass_id'    => $class->id,
                        'coupon_id'         =>  null,
                        'remarks'           => '',
                        'start_date'        => now(),
                        'end_date'          => $class->end_date,
                        'created_by'        => auth()->id(),

                    ];

                    StudentRegistration::create($registrationData);
                }
            }
        }
    }

    private function calculateAmountWithTax($amount, $collection, $onlyAmount = true)
    {
        $subscription_included = true;
        // $sa = new SubscriptionAdapter($collection->vendor_id);
        // $amountData = $sa->process($amount, $collection->collection_type, null, $subscription_included);
        $amountData = TaxFeeHelper::getTaxCalculationData($collection->vendor_id, $amount, $collection->collection_type, null, $subscription_included);
        if ($onlyAmount) {
            return  $amountData['total_amount'];
        }

        return  $amountData;
    }
}
