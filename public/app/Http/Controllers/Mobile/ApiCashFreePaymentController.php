<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Order;
use Illuminate\Http\Request;

class ApiCashFreePaymentController extends Controller
{
    public function paymentflow(Request $request, $id, $payid)
    {
        $order = Order::where('id', $id)->where('code', $payid)->first();
        if (! $order) {
            return redirect()->route('cancel_cashfree');
        }
        $mode = config('app.cash_free.mode');
        $url = config('app.cash_free.url');
        // info([$order, $mode, $id, $payid, $url]);
        return view('payment.cashfree.paymentflow', compact('payid','id', 'mode', 'url'));
    }

    public function apiPaymentData($id, $payid)
    {
        $order = Order::where('id', $id)->where('code', $payid)->first();
        if (! $order) {
            return response()->json(['message '=> 'no data'], 400);
        }
        // info([$order, json_decode($order->pg_request_data), $id, $payid]);
        return response(['data' =>  json_decode($order->pg_request_data)], 200);
    }

    public function mobilePaymentNotify(Request $r)
    {
        return response(['data' =>  $r], 200);
    }

    public function paymentStatus($id, $payid, Request $r)
    {
        try {
            if (! $r->hasValidSignature()) {
                return redirect()->route('cancel_cashfree');
            }
            $order = Order::where('id', decrypt($id))->where('code', $payid)->first();
            if (! $order) {
                return redirect()->route('cancel_cashfree');
            }
            $orderId = $r->orderId;
            $orderAmount = $r->orderAmount;
            $referenceId = $r->referenceId;
            $txStatus = $r->txStatus;
            $paymentMode = $r->paymentMode;
            $txMsg = $r->txMsg;
            $txTime = $r->txTime;
            $signature = $r->signature;
            $data = $orderId.$orderAmount.$referenceId.$txStatus.$paymentMode.$txMsg.$txTime;
            $hash_hmac = hash_hmac('sha256', $data, config('app.cash_free.secret_key'), true);
            $computedSignature = base64_encode($hash_hmac);
            if ($signature == $computedSignature) {
                return redirect()->route('success_cashfree');
            } else {
                return redirect()->route('cancel_cashfree');
            }
        } catch (\Throwable $th) {
            return redirect()->route('cancel_cashfree');
        }

        // if ($r->razorpay_payment_id and $r->razorpay_signature and $r->razorpay_order_id) {
        //     $data = [
        //         'razorpay_payment_id' => $r->razorpay_payment_id,
        //         'razorpay_signature' => $r->razorpay_signature,
        //         'razorpay_order_id' => $r->razorpay_order_id
        //     ];
        //     return redirect()->route('success_razor', $data);
        // } else {
        //     return redirect()->route('cancel_cashfree');
        // }
    }
}
