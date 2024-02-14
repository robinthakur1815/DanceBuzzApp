<?php

namespace App\Http\Controllers;

use App\Model\PartnerCollection as DataCollection;
use App\Enums\ReviewStatus;
use App\Helpers\UserHelper;
use App\Http\Resources\ReviewCollection;
use App\Http\Resources\Comment as WebComment;
use App\Http\Resources\CommentCollection as WebCommentCollection;
use App\ProductReviews;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Validator;

class ReviewController extends Controller
{
    public function getAllComments(Request $request)
    {
        $comments = ProductReviews::where('review_status', ReviewStatus::Approved)->latest();

        if ($request->collection_id) {
            $comments = $comments->where('collection_id', $request->collection_id);
        }

        if ($request->created_by) {
            $comments = $comments->orWhere(function ($q) use ($request) {
                $q = $q->where('created_by', $request->created_by);
                if ($request->collection_id) {
                    $q = $q->where('collection_id', $request->collection_id);
                }
            });
        }
        $comments = $comments->get();

        return new WebCommentCollection($comments);
    }

    public function updateCommentStatus(Request $request)
    {
        $user = auth()->user();
        $reviewIds = $request->reviewIds;
        foreach ($reviewIds as $id) {
            $comment = ProductReviews::find($id);
            if (! $comment) {
                return response(['errors' =>  ['Review not Found'], 'status' => false, 'message' => ''], 422);
            }

            $data = [
                'review_status' => $request->status,
                'approved_at' => $request->status == ReviewStatus::Approved ? Carbon::now() : null,
                'approved_by' => ($request->status == ReviewStatus::Approved && $user) ? $user->id : null,
            ];
            $comment->update($data);
        }

        return response(['message' =>  'Review status updated successfully', 'status' => false], 200);
    }

    public function saveComment(Request $request)
    {
        $user = User::find($request->user_id);
        if ($user) {
            $data = [
                'comment' => $request->comment,
                'collection_id' => $request->collection_id,
                'status' => ReviewStatus::Submitted,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];

            $comment = ProductReviews::create($data);

            return response(['message' =>  'Comment restored successfully', 'status' => false, 'data' => new WebComment($comment)], 200);
        }

        return response(['message' =>  'Unauthorized user', 'status' => false], 422);
    }

    public function deleteComment(Request $request)
    {
        $review = ProductReviews::where('id', $request->comment_id)->where('created_by', $request->user_id)->first();
        if (! $review) {
            return response(['message' =>  'review not found', 'status' => false], 422);
        }
        $review->delete();
        return response(['message' =>  'review deleted successfully', 'status' => true], 200);
        return response(['message' =>  'Unauthorized user', 'status' => false], 422);
    }

    public function restoreMultipleReviews(Request $request)
    {
      
        $reviewIds = $request->reviewIds;

        $reviews = ProductReviews::withTrashed()->whereIn('id', $reviewIds)->get();

        foreach ($reviews as $review) {
            $review->restore();
        }

        return response(['message' =>  'Review restored successfully', 'status' => true], 200);
    }

    public function getallReviews(Request $request)
    {
        $reviews = ProductReviews::with('collection')->latest();

        if ($request->search) {
            $searchText = $request->search;
            $reviews = $reviews->where(
                function ($q) use ($searchText) {
                    $q = $q->where('comment', 'like', "%{$searchText}%")
                        ->orWhereHas('purchaser_name', 'like', "%{$searchText}%")
                        ->orWhereHas(
                            'approvedBy',
                            function ($query) use ($searchText) {
                                $query->where('name', 'like', "%{$searchText}%");
                            }
                        );
                }
            );
        }

        if ($request->collectionId) {
            $reviews = $reviews->where('collection_id', $request->collectionId);
        }

        if ($request->status) {
            $reviews = $reviews->where('review_status', $request->status);
        }

        if ($request->isTrashed) {
            $reviews = $reviews->onlyTrashed();
        }

        if ($request->maxRows) {
            $reviews = $reviews->paginate($request->maxRows);
        } else {
            $reviews = $reviews->get();
        }

        // $reviews->map(function ($item) {
        //     $item->show_collection =  true;
        // });

        return new ReviewCollection($reviews);
    }

    public function deleteMultipleReviews(Request $request)
    {
        // if ($user->role == UserRole::Guest) {
        // $user = auth()->user();
        $reviewIds = $request->reviewIds;

        $reviews = ProductReviews::whereIn('id', $reviewIds)->get();

        foreach ($reviews as $review) {
            // $comment = ProductReviews::find($id);
            if (! $review) {
                return response(['errors' =>  ['review not found'], 'status' => false, 'message' => ''], 422);
            }
            $review->delete();
        }

        return response(['message' =>  'reviews deleted successfully', 'status' => true], 200);
    }

    /**
     * submit review from event details.
     *
     * @param Request
     * @return json
     */
    public function authSubmitReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'collection_id'     => 'required',
            'reviewText'        => 'required',
            'purchaser_id'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }

        $response = ['status' => false, 'message' => 'Unauthenticated person', 'data' => null];

        $user = auth()->user();
        if ($user == null) {
            return response()->json($response, 422);
        }
       
        $product = DataCollection::find($request->collection_id);

        if ($product) {
            $data = [
                'collection_id'     => $request->collection_id,
                'product_id'        => $request->product_id,
                'review'            => $request->reviewText,
                'purchaser_id'      => $user->id,
                'purchaser_name'    => $user->name,
                'purchaser_avatar'  => $user->avatar,
                'rating'            => $request->rating,
            ];

            $existingReview = ProductReviews::where('purchaser_id', $request->purchaser_id)
                ->where('collection_id', $request->collection_id)
                ->first();

            if ($existingReview) {
                $data['review_status'] = ReviewStatus::Submitted;
                $existingReview->update($data);
                $existingReview->save();

                $response['data'] = $existingReview;
                $response['message'] = 'Review successfully updated';
            } else {
                $review = ProductReviews::create($data);
                $response['data'] = $review;
                $response['message'] = 'Review successfully added';
            }
            $response['status'] = true;

            return response()->json($response, 200);
        } else {
            $response['message'] = 'Product not found';

            return response()->json($response, 422);
        }

        return response()->json($response, 200);
    }

    /**
     * submit review from event details.
     *
     * @param Request
     * @return json
     */
    public function authDeleteReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'review_id'        => 'required',
            'purchaser_id'      => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false, 'message' => ''], 422);
        }
        $response = ['status' => false, 'message' => 'Unauthenticated person', 'data' => null];

        $user = auth()->user();
        if ($user == null) {
            return response()->json($response, 422);
        }
       
        $review = DB::connection('partner_mysql')->table('product_reviews')->where('purchaser_id', $user['id'])
            ->where('id', $request->review_id)
            ->first();

        if (! $review) {
            return response(['message' =>  'review not found', 'status' => false], 422);
        }

        $review = DB::connection('partner_mysql')->table('product_reviews')->where('purchaser_id', $user['id'])
        ->where('id', $request->review_id);
        $review->delete();

        return response()->json(['status' => true, 'message' => 'review deleted successfully', 'data' => null], 200);
    }

    /**
     * Get details of all reviews for a collection.
     *
     * @param  int $id
     * @return \App\ProductReviews $orders
     */
    public function getCollectionReviews(Request $request)
    {
        $reviews = ProductReviews::where('review_status', ReviewStatus::Approved)->latest();

        if ($request->collection_id) {
            $reviews = $reviews->where('collection_id', $request->collection_id);
        }

        if ($request->product_id) {
            $reviews = $reviews->where('product_id', $request->product_id);
        }

        if ($request->purchaser_id) {
            $reviews = $reviews->orWhere(function ($q) use ($request) {
                $q = $q->where('purchaser_id', $request->purchaser_id);
                if ($request->collection_id) {
                    $q = $q->where('collection_id', $request->collection_id);
                }
                if ($request->product_id) {
                    $q = $q->where('product_id', $request->product_id);
                }
            });
        }

        if ($request->max_rows) {
            $reviews = $reviews->paginate($request->max_rows);
        } else {
            $reviews = $reviews->get();
        }

        $userIds = [];
        $users = collect([]);
        foreach ($reviews as  $review) {
            $userIds[] = $review->purchaser_id;
        }

        if (count($userIds)) {
            $users = UserHelper::users($userIds);
        }

        $reviews->getCollection()->transform(function ($review) use ($users) {
            $user = $users->where('id', $review->purchaser_id)->first();
            if ($user) {
                $review->purchaser_name = Str::title($user->name);
                $review->purchaser_avatar = $user->avatar;
            }

            return $review;
        });

        if ($request->purchaser_id) {
            $review = ProductReviews::where('purchaser_id', $request->purchaser_id)
                ->where('collection_id', $request->collection_id)
                ->first();
            $users = UserHelper::users([$request->purchaser_id]);
            if (count($users)) {
                $user = $users->first();
                if ($user and $review) {
                    $review->purchaser_name = Str::title($user->name);
                    $review->purchaser_avatar = $user->avatar;
                }
            }

            return ['reviews' => $reviews, 'auth_review' => $review];
        }

        return ['reviews' => $reviews, 'auth_review' => ''];
    }
}
