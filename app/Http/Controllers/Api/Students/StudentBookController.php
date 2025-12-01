<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\order\OrderItem;
use App\Models\shop\Product;
use Illuminate\Http\Request;
class StudentBookController extends Controller
{
    public function index()
    {
        $userId = auth()->id();
        $productList = OrderItem::whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->where('payment_status', 'paid');
        })
            ->with('product.category', 'product.language')
            ->paginate(10);

        $productList->getCollection()->transform(function ($orderItem) {
            $product = $orderItem->product;
            return [
                'order_item_id' => $orderItem->id,
                'id' => $product->id,
                'title' => $product->title,
                'file' => $product->file ? api::api . $product->file : null,
                'image' => $product->first_image,
                'category' => $product->category->title,
                'is_physical' => $product->category->is_physical,
                'flag' => api::api . $product->language->flag,
            ];
        });
        return api_response($productList);
    }
    public function index_dow()
    {
        $userId = auth()->id();

        $purchasedProductList = OrderItem::whereHas('order', function ($q) use ($userId) {
            $q->where('user_id', $userId)
                ->where('payment_status', 'paid');
        })
            ->whereHas('product.category', function ($q) {
                $q->where('is_physical', 0);
            })
            ->with('product.category', 'product.language')
            ->paginate(10);

        // کتاب‌های رایگان و دانلودی
        $freeProductList = Product::whereHas('category', function ($q) {
            $q->where('is_physical', 0);
        })
            ->whereHas('sellers', function ($q) {
                $q->where(function ($q) {
                    $q->where('discounted_price', 0)
                        ->orWhere('price', 0);
                });
            })
            ->with('category', 'language')
            ->get();

        // ترکیب دو لیست
        $finalProductList = $purchasedProductList->getCollection()->transform(function ($orderItem) {
            $product = $orderItem->product;
            return [
                'id' => $product->id,
                'title' => $product->title,
                'file' => $product->file ? api::api . $product->file : null,
                'image' => $product->first_image,
                'category' => $product->category->title,
                'is_physical' => $product->category->is_physical,
                'flag' => api::api . $product->language->flag,
            ];
        });

        $freeProductList->transform(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'file' => $product->file ? api::api . $product->file : null,
                'image' => $product->first_image,
                'category' => $product->category->title,
                'is_physical' => $product->category->is_physical,
                'flag' => api::api . $product->language->flag,
            ];
        });

        // ترکیب کتاب‌های خریداری شده با کتاب‌های رایگان
        $finalProductList = $finalProductList->merge($freeProductList);

        return api_response($finalProductList);
    }

    public function suggestedBooks()
    {
        $books = Product::inRandomOrder() // برای پیشنهاد تصادفی
        ->with('category', 'language')
            ->take(3)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'title' => $product->title,
                    'file' => $product->file ? api::api . $product->file : null,
                    'image' => $product->first_image,
                    'category' => $product->category->title,
                    'is_physical' => $product->category->is_physical,
                    'flag' => $product->language ? api::api . $product->language->flag : null,
                ];
            });

        return api_response($books);
    }

    public function showBook($id)
    {
        $book = Product::where('id', $id)
            ->with('category', 'language')
            ->first();

        if (!$book) {
            return api_response(null, 'کتاب مورد نظر پیدا نشد', 404);
        }

        $data = [
            'id' => $book->id,
            'title' => $book->title,
            'author' => $book->author,
            'Time_to_print' => $book->Time_to_print,
            'volume_number' => $book->Volume_number,
            'publication' => $book->publication ? $book->publication->title : null,
            'age_group' => $book->age_group->title,
            'subject' => $book->goal->goal->title . ' ' . $book->goal->title,
            'selected_Seller' => $book->selected_Seller,
            'price' => $book->default_price,
            'language_level' => $book->language_level->title,
            'file' => $book->file ? api::api . $book->file : null,
            'image' => $book->first_image,
            'category' => $book->category->title,
            'is_physical' => $book->category->is_physical,
            'flag' => $book->language->icon,
            'language' => $book->language->title,
        ];

        return api_response($data);
    }

    public function show($orderItemId)
    {
        $userId =  auth()->id(); // در صورت نیاز، یا استفاده از احراز هویت واقعی

        $orderItem = OrderItem::where('id', $orderItemId)
            ->whereHas('order', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->where('payment_status', 'paid');
            })
            ->with([
                'order',
                'product.category',
                'product.language'
            ])
            ->first();

        if (!$orderItem) {
            return api_response(null, 'آیتم مورد نظر یافت نشد', 404);
        }

        $book = $orderItem->product;
        $order = $orderItem->order;

        $data = [
            'product' => [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author,
                'Time_to_print' => $book->Time_to_print,
                'volume_number' => $book->Volume_number,
                'publication' => $book->publication ? $book->publication->title : null,
                'age_group' => $book->age_group->title,
                'subject' => $book->goal->goal->title . ' ' . $book->goal->title,
                'selected_Seller' => $book->selected_Seller,
                'price' => $book->default_price,
                'language_level' => $book->language_level->title,
                'file' => $book->file ? api::api . $book->file : null,
                'image' => $book->first_image,
                'category' => $book->category->title,
                'is_physical' => $book->category->is_physical,
                'flag' => api::api . $book->language->flag,
                'language' => $book->language->title,
            ],
            'order' => [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'discount' => ($order->total_price - $order->final_price / $order->total_price) * 100,
                'seller' => $order->seller->name,
                'payment_status' => $order->payment_status,
                'created_at' => jdate($order->created_at)->format('Y/m/d H:i'),
                'price' => $orderItem->unit_price ?? null,
            ],
            'addresses' => [
                'country' => $order->buyerAddress->country->title,
                'city' => $order->buyerAddress->city->name,
                'address' => $order->buyerAddress->full_address,
                'postal_code' => $order->buyerAddress->postal_code,
                'name' => $order->buyerAddress->buyer->first_name . ' ' . $order->buyerAddress->buyer->last_name,
                'mobile' => $order->buyerAddress->mobile,

            ],
            'date' => $orderItem->created_at,
        ];

        return api_response($data);
    }


}
