<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Helpers\UserHelper;
use App\Http\Resources\WebOrder as WebOrderResource;
use App\Http\Resources\WebOrderCollection;
use App\Order;
use App\Product;
use Illuminate\Http\Request;

class WebOrderController extends Controller
{
    public function index()
    {
        $products = Product::with('prices.discounts', 'prices.medias', 'orders', 'productReviews')->find(25);

        return new WebOrderResource($products);
    }

    public function getAllUserTransactions(Request $request)
    {
        // $purchasers = [];
        $user = UserHelper::validateUser($request->user_id, $request->bearerToken());
        $user = $user['data'];
        if ($user == null) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated person', 'data' => null], 422);
        }
        $purchasers[] = $user['id'];
        if ($user['student_count'] > 0) {
            $purchasers = array_merge($purchasers, $user['student_ids']);
        }

        $transactions = Order::filtered($request, $purchasers);

        return new WebOrderCollection($transactions);
        // return $transactions;
    }
}
