<?php

namespace App\Http\Controllers\Mobile;

use App\Collection as AppCollection;
use App\Enums\CollectionType;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CollectionCRequest;
use App\Http\Requests\CollectionURequest;
use App\Http\Resources\MobileData as MobileDataResource;
use App\Http\Resources\MobileDataCollection;
use Illuminate\Http\Request;

class VendorCollectionController extends Controller
{
    public function index($vendorId, Request $request)
    {
        $search = $request->search;
        $status = $request->status;

        $type = $request->type;
        $types = [CollectionType::events, CollectionType::workshops];

        if (! in_array($type, $types)) {
            return response(['errors' => ['error' => ['invalid request']], 'status' => false, 'message' => ''], 422);
        }
        
        $allEvents = AppCollection::where('collection_type', $type)
                                ->where(function ($q) use ($vendorId) {
                                    $q->whereNotNull('vendor_id')
                                    ->where('vendor_id', $vendorId);
                                });

        if ($search) {
            $allEvents = $allEvents->where('title', 'like', "%${search}%");
        }

        if ($status) {
            $endDate = now()->format('Y/m/d');
            if ($status == PublishStatus::Published) {
                $allEvents = $allEvents->where('status', (int) PublishStatus::Published)
                                       ->where('saved_content->end_date', '>=', $endDate);
            } else {
                $allEvents = $allEvents->where(function ($q) use ($endDate) {
                    $q->where('status', '!=', PublishStatus::Published)
                    ->orWhere('saved_content->end_date', '<', $endDate);
                });
            }
        }

        $allEvents = $allEvents->with(['product.prices.discounts'])->latest()->paginate(10);
        $allEvents->getCollection()->transform(function ($event) use ($vendorId, $search) {
            $event->vendorId = $vendorId;
            $event->search = $search;
            $event->published_content = json_decode($event->saved_content);
            $event->ischeck = true;
            $event->partner = true;

            return $event;
        });

        return new MobileDataCollection($allEvents);
    }

    public function show($vendorId, $id, Request $request)
    {
        $event = AppCollection::where('id', $id)
                    ->whereNotNull('vendor_id')
                    ->where('vendor_id', $vendorId)
                    ->with(['product.prices.discounts'])
                    ->first();

        $edit = $request->edit;
        $mobile = $request->mobile;

        if (! $event) {
            return response(['errors' => ['error' => ['event not found']], 'status' => false, 'message' => ''], 422);
        }

        $event->published_content = json_decode($event->saved_content);

        $event['isdetails'] = true;
        $event['ischeck'] = true;
        $event['partner'] = true;
        $event['mobile'] = true;

        if ($edit) {
            $event['edit'] = true;
        }

        return new MobileDataResource($event);
    }

    public function store($vendorId, CollectionCRequest $request)
    {
        return response(['status' => true, 'message' => 'success'], 201);
    }

    public function update($vendorId, CollectionURequest $request)
    {
        return response(['status' => true, 'message' => 'updated'], 200);
    }

    public function delete($vendorId, $id)
    {
        // code...
    }

    public function changeStatus($vendorId, $id, Request $request)
    {
        $status = $request->status;
        $types = [PublishStatus::Draft, PublishStatus::Published];

        if (! in_array($status, $types)) {
            return response(['errors' => ['error' => ['invalid request']], 'status' => false, 'message' => ''], 422);
        }

        $event = AppCollection::where('id', $id)
                ->whereNotNull('vendor_id')
                ->where('vendor_id', $vendorId)
                ->with(['product.prices.discounts'])
                ->first();

        if (! $event) {
            return response(['errors' => ['error' => ['event not found']], 'status' => false, 'message' => ''], 422);
        }

        $event->update(['status' => $status]);

        return response(['status' => true, 'message' => 'updated'], 200);
    }

    public function editor($id = null)
    {
        $data = '';
        if ($id) {
            $collection = AppCollection::where('id', $id)->first();
            if ($collection) {
                $contentData = json_decode($collection->saved_content);
                if (isset($contentData->content) and $contentData->content) {
                    $data = $contentData->content;
                }
            }
        }

        return view('editor.editor', compact('data'));
    }
}
