<?php

namespace App\Http\Controllers\Api\seller;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Financial\Transaction;
use App\Models\order\Order;
use App\Models\Seller\ProductSeller;
use App\Models\shop\Product;
use App\Models\shop\Story;
use Illuminate\Http\Request;

class SellerDashboardController extends Controller
{
    public function index()
    {
//        $user = auth()->user();
      $user = 49;
        $stories = Story::where('is_approved', 1)->where('user_id' , $user)->get();


        $sellerId = auth()->user()->seller->id;
        $api = api::api ;

        $baseQuery = Order::where('seller_id', $sellerId)
            ->with(['user', 'items.product.images'])
            ->latest();

        $paid = (clone $baseQuery)
            ->where('status', 'paid')
            ->where('seller_approval_status', 'pending')
            ->take(3)
            ->get();


        $going_ready = (clone $baseQuery)
            ->where('status', 'paid')
            ->where('seller_approval_status', 'approved')
            ->take(3)
            ->get();


        $formatOrders = function ($orders) use ($api) {
            return $orders->map(function ($order) use ($api) {
                return [
                    'order_id' => $order->order_code,
                    'tracking_code' => $order->tracking_code,
                    'order_date' => jdate($order->created_at)->format('Y/m/d'),
                    'total_price' => $order->total_price,
                    'address' => $order->address,
                    'ravesh' => $order->sending_method,
                    'customer_name' => $order->user->name,
                    'customer_phone' => $order->user->mobile,
                    'items' => $order->items->map(function ($item) use ($api) {
                        $imagePath = $item->product->images->first()?->path;
                        return [
                            'product_name' => $item->product->title,
                            'quantity' => $item->quantity,
                            'Volume_number' => $item->product->Volume_number,
                            'category' => $item->product->category->title,
                            'image' => $imagePath ? $api . $imagePath : null,
                        ];
                    }),
                ];
            });

        };

        $approved = ProductSeller::where('seller_id',  $sellerId)->whereRelation('product','status' , '=', 'approved')->take(4)->get();
        $pending = ProductSeller::where('seller_id',  $sellerId)->whereRelation('product','status' , '=', 'pending')->take(4)->get();
        $rejected = ProductSeller::where('seller_id',  $sellerId)->whereRelation('product','status' , '=', 'rejected')->take(4)->get();
        $return = function ($products) use ($api) {
            return $products->map(function ($product) {
                return [
                    'status' => $product->product->status,
                    'id' => $product->product_id,
                    'title' => $product->product->title,
                    'price' => $product->price,
                    'off_price' => $product->discounted_price,
                    'quantity' => $product->quantity,
                    'is_active' => $product->is_active,
                    'Volume_number' => $product->product->Volume_number,
                    'category' => $product->product->category->title,
                    'sell_data' => $product->sales_data,
                ];
            });
        };



        $user = auth()->user();

        $wallet = $user->wallet ?? $user->wallet()->create([
            'balance' => 0,
        ]);
        $tasvie = Transaction::whereRelation('wallet', 'user_id', $user)
            ->where('type', 'withdraw')->where('status', 'approved')->sum('amount');
        $dar_tasvie = Transaction::whereRelation('wallet', 'user_id', $user)
            ->where('type', 'withdraw')->where('status', 'pending')->sum('amount');

        return api_response([
            'story' =>$stories,
            'paid' => $formatOrders($paid),
            'going_ready' => $formatOrders($going_ready),
            'approved' => $return($approved),
            'pending' => $return($pending),
            'rejected' => $return($rejected),
            'balance' => $wallet->balance,
            'delivered' => $wallet->balance - ($wallet->balance * 0.25),
            'tasvie' => $tasvie,
            'dar_tasvie' => $dar_tasvie,
            'maliat' => $wallet->balance * 0.25,
            ]);

    }
}
