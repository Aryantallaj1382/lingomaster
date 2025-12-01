<?php

namespace App\Http\Controllers\Api\buyer;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\order\Order;
use App\Models\order\OrderItem;
use App\Models\ProductView;
use App\Models\shop\Product;
use Illuminate\Http\Request;

class DashboardBuyerController extends Controller
{
    public function index(Request $request)
    {

        $pending = Order::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->take(2)
            ->pluck('id');
        $pending_count = Order::where('user_id', auth()->id())
            ->where('status', 'pending')
            ->count();

        $firstItems = OrderItem::select('order_items.*')
            ->joinSub(
                OrderItem::selectRaw('MIN(id) as id')
                    ->whereIn('order_id', $pending)
                    ->groupBy('order_id'),
                'firsts',
                'order_items.id',
                '=',
                'firsts.id'
            )
            ->with('product')
            ->get();


        $pen = $firstItems->map(function ($item) {
            return [
                'image'      => $item->product?->first_image,
                'title'      => $item->product?->goal?->title,
                'category'   => $item->product?->category?->title,
                'date' => $item->created_at,
                'id' => $item->order->id,

            ];
        });

        $delivered = Order::where('user_id', auth()->id())
            ->where('status', 'delivered')
            ->take(2)
            ->pluck('id');

        $delivered_count = Order::where('user_id', auth()->id())
            ->where('status', 'delivered')
            ->count();

        $delivered_items = OrderItem::select('order_items.*')
            ->joinSub(
                OrderItem::selectRaw('MIN(id) as id')
                    ->whereIn('order_id', $delivered)
                    ->groupBy('order_id'),
                'firsts',
                'order_items.id',
                '=',
                'firsts.id'
            )

            ->with(['product.goal', 'product.category']) // همه روابط لازم رو باهم لود کن
            ->get();

        $delivered = $delivered_items->map(function ($item) {
            return [
                'image'      => $item->product?->first_image,
                'title'      => $item->product?->goal?->title,
                'category'   => $item->product?->category?->title,
                'date' => $item->created_at,
                'id' => $item->order->id,
            ];
        });
        ////
        $returned = Order::where('user_id', auth()->id())
            ->where('status', 'returned')
            ->take(2)
            ->pluck('id');

        $returned_count = Order::where('user_id', auth()->id())
            ->where('status', 'returned')
            ->count();

        $returned_items = OrderItem::select('order_items.*')
            ->joinSub(
                OrderItem::selectRaw('MIN(id) as id')
                    ->whereIn('order_id', $returned)
                    ->groupBy('order_id'),
                'firsts',
                'order_items.id',
                '=',
                'firsts.id'
            )

            ->with(['product.goal', 'product.category']) // همه روابط لازم رو باهم لود کن
            ->get();

        $retur = $returned_items->map(function ($item) {
            return [
                'image'      => $item->product?->first_image,
                'title'      => $item->product?->goal?->title,
                'category'   => $item->product?->category?->title,
                'id' => $item->order->id,
                'date' => $item->returned_date,
            ];
        });
        ///

        $viewedProductIds = ProductView::where('user_id', auth()->id())
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get();


        $return = $viewedProductIds->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'image' => $item->product->first_image,
                'selected_seller' => $item->product->selected_seller,
                'category' => $item->product->category->title ?? null,
                'language' => $item->product->language,
                'is_like' => $item->product->is_liked,


            ];
        });


        $randomProducts = Product::select('title', 'category_id','id','is_download')
            ->whereRelation('sellers' , 'id' , 1)

            ->with('images','language','category')
            ->inRandomOrder()
            ->take(10)
            ->get()
            ->map(function ($product) {
                $isLiked = auth()->check() ? $product->isLikedByUser(auth()->user()->id) : false;


                return [
                    'title' => $product->title,
                    'price' => $product->default_price,
                    'off_price' => $product->default_discounted_price,
                    'discount_percentage' => $product->default_discount_percentage,
                    'discount_expiration' => $product->default_discount_expire_at,
                    'language' => $product->language,
                    'category' => $product->category->title ??null,
                    'id' => $product->id,
                    'image' => $product->first_image,
                    'selected_seller' => $product->selected_seller,
                    'is_like' => $isLiked,
                    'is_download' => $product->is_download,
                ];
            });

        return api_response(
                [
                    'pending' =>[
                        'count' => $pending_count,
                        'items' => $pen,
                    ],
                    'delivered' =>[
                        'count' => $delivered_count,
                        'items'=>$delivered,
                    ] ,
                    'returned' =>[
                        'count' => $returned_count,
                        'items'=>$retur,
                    ] ,

                    'recently' => $return,
                    'product' => $randomProducts,
                ]
        );

    }
    public function discount(Request $request)
    {
        $category =$request->query('category');

        $productsWithDiscount = Product::with('images', 'language', 'category')
            ->where('category_id', $category)
            ->hasDiscount()
            ->take(6)
            ->get()
            ->map(function ($product) {

                return [
                    'title' => $product->title,
                    'price' => $product->default_price,
                    'off_price' => $product->default_discounted_price,
                    'discount_percentage' => $product->default_discount_percentage,
                    'discount_expiration' => $product->default_discount_expire_at,
                    'language' => $product->language,
                    'category' => $product->category->title ?? null,
                    'image' => $product->first_image,
                    'selected_seller' => $product->selected_seller,
                    'is_like' => $product->is_liked,
                    'is_download' => $product->is_download,
                    'id' => $product->id,

                ];
            });
        return api_response($productsWithDiscount);

    }
}
