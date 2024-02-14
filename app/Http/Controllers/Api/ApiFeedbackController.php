<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeedbackCollection as FeedbackCollection;
use App\Model\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class ApiFeedbackController extends Controller
{
    public function index(Request $request)
    {
        return $this->feedbacks($request);
    }

    public function show(Request $request, $id)
    {
        $request['id'] = $id;

        return $this->feedbacks($request);
    }

    public function create(Request $request)
    {
        $user = auth()->user();
        try {
            $validator = Validator::make($request->all(), [
                'app_type'      =>  'required|int',
                // 'type'          =>  'required|int',
                'rating'        =>  'required|int',
                'platform_type' =>  'required|int',
                'app_type'      =>  'required|int',
                'meta'          =>  'required',
                'category_id'   =>  'required|int',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
            }

            $meta = json_encode([
                'meta'     => $request->meta,
                'request'  => $request,
                'app_type' => $request->app_type,
            ]);

            $data = [
                'type'          => $request->type ? $request->type : null,
                'rating'        => $request->rating,
                'description'   => $request->description,
                'platform_type' => $request->platform_type,
                'meta'          => $meta,
                'user_id'       => $user ? $user->id : null,
                'app_type'      => $request->app_type,
                'category_id'   => $request->category_id
            ];

            DB::transaction(function () use (&$data, $request) {
                $feedback = Feedback::create($data);
                $files = $request->file('attachments');

                // foreach($files as $fileData){
                //     foreach($fileData as $file){
                //         info($file);
                //     }
                // }
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $this->uploadMedias($file, $feedback);
                    }
                }
            });

            return response()->json(['message' => 'Successfully submitted'], 200);
        } catch (\Exception $e) {
            report($e);

            return response(['message' =>  'server error', 'status' => false], 500);
        }
    }

    private function feedbacks(Request $request)
    {
        $feedbacks = Feedback::with('medias', 'category');

        if (isset($request['id']) and $request['id']) {
            $feedbacks = $feedbacks->where('id', $request['id'])->first();

            return $feedbacks;
        }

        if (isset($request['app_type']) and $request['app_type']) {
            $feedbacks = $feedbacks->where('app_type', $request['app_type']);
        }

        if (isset($request['feedback_type']) and $request['feedback_type']) {
            $feedbacks = $feedbacks->where('type', $request['feedback_type']);
        }
        if (isset($request['platform_type']) and $request['platform_type']) {
            $feedbacks = $feedbacks->where('platform_type', $request['platform_type']);
        }
        if (isset($request['category_type']) and $request['category_type']) {
            $category = $request['category_type'];
            $feedbacks = $feedbacks->whereHas('category', function ($q) use ($category) {
                $q->where('name', $category);
            });
        }
        if (isset($request['maxRows']) and $request['maxRows']) {
            $feedbacks = $feedbacks->latest()->paginate($request['maxRows']);
        } else {
            $feedbacks = $feedbacks->latest()->get();
        }
        //return $feedbacks;
        return new FeedbackCollection($feedbacks);
    }

    private function uploadMedias($file, $feedback)
    {
        try {
            $fileName = $file->getClientOriginalName();
            $mediaAdapter = new ImageHelper;
            $fileData = $mediaAdapter->uploadFileMobileFeedBackClient($file);
            $mediadata = [
                'name'       => $fileName,
                'media_id'   => $fileData->id,
                'created_by' => null,
            ];
            $feedback->mediables()->create($mediadata);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
