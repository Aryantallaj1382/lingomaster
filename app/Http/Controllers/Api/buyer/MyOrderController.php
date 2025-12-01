<?php

namespace App\Http\Controllers\Api\buyer;

use App\Http\Controllers\Controller;
use App\Models\Financial\Transaction;
use App\Models\order\Order;
use App\Models\SystemSetting;
use App\Services\SmsServiceServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use function Symfony\Component\String\s;

class MyOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $status = $request->query('status');
        $query = Order::where('user_id', $user->id)->where('payment_status', 'paid');
        if ($status) {
            $query->where('status', $status);
        }
        $orders = $query->with('items.product')->paginate(10);
        $orders->getCollection()->transform(function ($order) {
            return [
                'id' => $order->id,
                'status' => $order->seller_approval_status,
                'created_at' => $order->created_at,
                'tracking_code' => $order->tracking_code,
                'total_price' => $order->total_price,
                'delivery_date' => $order->delivery_date ?? null,
                'returned_date' => $order->returned_date ?? null,
                'returned_status' => $order->returned_status ?? null,
                'returned_reason' => $order->return_reason ?? null,
                'items' => $order->items->map(function ($item) {
                    return [
                        'quantity' => $item->quantity,
                        'id' => $item->product->id ?? null,
                        'title' => $item->product->title ?? null,
                        'image' => $item->product->first_image,  // استفاده از Accessor
                        'price' => $item->product->price ?? null,
                        'category' => $item->product->category->title ?? null,
                        'Volume_number' => $item->product->Volume_number ?? null,

                    ];
                }),
            ];
        });
        return api_response($orders);
    }

    public function show(Request $request, $id)
    {
        $order = Order::find($id);
        $shipping = SystemSetting::where('name', 'post')->value('value');
        $r = null;
        if ($order->status == 'cancelled') {
            $r = 'کاربر';
        } elseif ($order->seller_approval_status == 'rejected') {
            $r = 'لغو سیستمی';
        }

        $return = [
            'id' => $order->id,
            'status' => $order->seller_approval_status,
            'created_at' => $order->created_at,
            'tracking_code' => $order->tracking_code,
            'total_price' => $order->total_price,
            'payment_method' => $order->payment_method,
            'sending_method' => $order->sending_method,
            'delivery_date' => $order->delivery_date ?? null,
            'returned_date' => $order->returned_date ?? null,
            'returned_status' => $order->returned_status ?? null,
            'returned_reason' => $order->return_reason ?? null,
            'rejected_reason' =>$r,
            'rejected_date' => $order->updated_at ?? null,
            'address' => $order->buyerAddress->address,
            'mobile' => $order->user->mobile,
            'sending_price' => $order->sending_method === 'پست' ? number_format($shipping) . ' تومان' : 'پرداخت در محل',
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->product->id,
                    'title' => $item->product->title ?? null,
                    'quantity' => $item->quantity ?? null,
                    'image' => $item->product->first_image,
                    'price' => $item->product->price ?? null,
                    'category' => $item->product->category->title ?? null,
                    'Volume_number' => $item->product->Volume_number ?? null,
                ];
            })
        ];
        return api_response($return);
    }

    public function returnOrder(Request $request, $orderId)
    {
        $request->validate([
            'return_reason' => 'required|string|max:1000',
            'return_video' => 'nullable|file|mimes:mp4,mov,avi|max:20480', // حداکثر 20MB ویدیو
        ]);
        $order = Order::where('id', $orderId)
            ->where('user_id', auth()->id())
            ->firstOrFail();
        $data = [
            'is_returned' => true,
            'return_reason' => $request->return_reason,

        ];
        if ($request->hasFile('return_video')) {
            $path = $request->file('return_video')->store('return_videos', 'public');
            $data['return_video_path'] = $path;
        }
        $order->update($data);

        $smsService = new SmsServiceServices();

        $n = $order->seller->mobile;
        $smsService->sendPatternSms(
            '5vbbj83ogcn5p4g',
            $n,
            [
                'order_code' => $order->order_code,

            ]
        );
        return api_response([], 'مرجوعی و فایل ویدیویی با موفقیت ثبت شد.');
    }

    public function tracking(Request $request)
    {
        $code = $request->input('code');
        $order = Order::where('tracking_code', $code)->first();

        if (!$order) {
            return api_response([], 'با این کد سفارشی پیدا نشد', 404);
        }
        return api_response($order->status);
    }



    public function cancel($id)
    {
        $user = auth()->user();

        DB::transaction(function () use ($id, $user) {
            $order = Order::where('user_id', $user->id)->findOrFail($id);

            if (!in_array($order->status, ['pending', 'confirmed'])) {
                throw new \Exception('سفارش ارسال شده');
            }

            $price = $order->final_price;

            $order->update([
                'status' => 'cancelled',
            ]);

            $wallet = $user->wallet;
            $wallet->balance += $price;
            $wallet->save();
            Transaction::create([
                'type' => 'deposit',
                'amount' => $price,
                'description' => 'استرداد مبلغ سفارش لغو شده',
                'wallet_id' => $wallet->id,
                'status' => 'approved',
            ]);


        });

        return api_response( [],'سفارش با موفقیت لغو و مبلغ به کیف پول بازگشت داده شد.');
    }
    public function rateDeliveredOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_emtiyaz' => 'required|integer|between:1,10',
            'delivery_emtiyaz' => 'required|integer|between:1,10',
        ]);

        $order = Order::where('id', $request->order_id)
            ->where('user_id', auth()->id())
            ->where('status', 'delivered')
            ->first();

        if (!$order) {
            return api_response([], 'این سفارش یافت نشد یا هنوز تحویل داده نشده است.');
        }

        // جلوگیری از ثبت مجدد امتیاز
        if (!is_null($order->product_satisfaction) || !is_null($order->delivery_rating)) {
            return api_response([], 'شما قبلاً به این سفارش امتیاز داده‌اید.');
        }

        $order->update([
            'product_satisfaction' => $request->product_emtiyaz,
            'delivery_rating' => $request->delivery_emtiyaz,
        ]);

        return api_response([],'امتیاز شما با موفقیت ثبت شد.');
    }


}
