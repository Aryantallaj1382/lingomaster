<?php

namespace App\Http\Controllers\Api\shop;

use App\Helpers\zarinpal;
use App\Http\Controllers\Controller;
use App\Models\Buyer\BuyerAddress;
use App\Models\contacts\ShipingCost;
use App\Models\DiscountCodeShop;
use App\Models\DiscountForStudent;
use App\Models\Financial\Transaction;
use App\Models\order\Order;
use App\Models\payment\Payment;
use App\Models\shop\Category;
use App\Models\shop\Product;
use App\Models\shop\ProductSeller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Reverb\Loggers\Log;

class OrderController extends Controller
{


    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:buyer_addresses,id',
            'seller_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.category' => 'required|string',
            'code' => 'nullable|string',
            'payment_method' => 'required',
        ], [], [ // ← این بخش سوم برای معادل‌های فارسی فیلدهاست
            'address_id' => 'آدرس خریدار',
            'seller_id' => 'فروشنده',
            'items' => 'محصولات',
            'items.*.id' => 'شناسه محصول',
            'items.*.quantity' => 'تعداد محصول',
            'items.*.category' => 'دسته‌بندی محصول',
            'code' => 'کد تخفیف',
            'payment_method' => 'روش پرداخت',
        ]);
        $shipping_cost = $request->payment_method === 'post'
            ? SystemSetting::where('name', ShipingCost::post)->value('value')
            : SystemSetting::where('name', ShipingCost::tipax)->value('value');

        $user = auth()->user();
        $sellerId = $request->seller_id;
        $items = $request->items;
        $address = BuyerAddress::findOrFail($request->address_id);

        DB::beginTransaction();

        try {
            $totalPrice = 0;
            $discountableAmount = 0;
            $orderItems = [];

            // استخراج دسته‌بندی‌ها از ورودی
            $categoryTitles = collect($items)->pluck('category')->unique();
            $categoryMap = Category::whereIn('title', $categoryTitles)->get()->keyBy('title');

            // دریافت اطلاعات تخفیف
            $discount = null;
            if (!empty($request->code)) {
                $categoryIds = $categoryMap->pluck('id')->toArray();

                $discount = DiscountCodeShop::where('code', $request->code)
                    ->where('seller_id', $sellerId)
                    ->where(function ($query) use ($categoryIds) {
                        $query->whereIn('category_id', $categoryIds)
                            ->orWhere('all', true);
                    })->first();

                if (!$discount || ($discount->expire_at && now()->greaterThan($discount->expire_at)) || $discount->usage_limit === 0) {
                    $discount = null; // کد تخفیف معتبر نیست
                }
            }

            foreach ($items as $item) {
                $productId = $item['id'];
                $quantity = $item['quantity'];

                $ps = ProductSeller::where('product_id', $productId)
                    ->where('seller_id', $sellerId)
                    ->first();

                if (!$ps) {
                    throw new \Exception("محصول {$productId} برای این فروشنده یافت نشد.");
                }

                $price = $ps->price;
                if ($ps->discounted_price && $ps->discount_expire_at && now()->lt($ps->discount_expire_at)) {
                    $price = $ps->discounted_price;
                }

                $itemTotal = $price * $quantity;
                $totalPrice += $itemTotal;

                $isDiscountable = false;
                if ($discount) {
                    $itemCategory = $categoryMap[$item['category']] ?? null;
                    if ($discount->all || ($itemCategory && $discount->category_id == $itemCategory->id)) {
                        $isDiscountable = true;
                    }
                }

                if ($isDiscountable) {
                    $discountableAmount += $itemTotal;
                }

                $orderItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'total_price' => $itemTotal,
                ];
            }

            $discountAmount = $discount ? $discountableAmount * ($discount->parentage / 100) : 0;
            $finalPrice = $totalPrice - $discountAmount + $shipping_cost;

            $order = Order::create([
                'user_id' => $user->id,
                'seller_id' => $sellerId,
                'total_price' => $totalPrice,
                'final_price' => $finalPrice,
                'address' => $address->address,
                'buyer_address_id' => $address->id,
                'status' => 'pending',
                'payment_method' => $request->payment_method === 'post' ? 'پست' : 'تیپاکس',
                'sending_method' =>'درگاه پرداخت',
                'payment_status' => 'unpaid',
                'seller_approval_status' => 'pending',
                'order_code' => strtoupper(Str::random(10)),
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            DB::commit();

            $description = "پرداخت خرید محصول";
            $paymentService = new zarinpal();
            $callback = 'https://api.lingomasters.ir/zarin_callback_product';
            return $paymentService->pay($order, $finalPrice, $description, $callback);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'خطا در ثبت سفارش: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function zarin_callback_product(Request $request)
    {
        if (!$request->filled('Authority') || !$request->filled('Status'))
            return api_response([], 'خطا در پاسخ درگاه پرداخت', 403);

        $order_payment = Payment::where('authority', '=', $request->Authority)->first();


        if (blank($order_payment))
            return api_response([], 'اطلاعات پرداخت حذف شده است', 403);


        $amount = $order_payment->amount;
        $authority = $order_payment->authority;
        $response = zarinpal()
            ->merchantId(config('zarinpal.merchant_id'))
            ->amount($amount)
            ->verification()
            ->authority($authority)
            ->send();
        $redirect_url = 'https://lingomasters.ir/wallet';

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $payable = $order_payment->payable;
            if ($payable instanceof Order) {
                $payable->update([
                    'payment_status' => 'failed',
                    'status' => 'cancelled',
                ]);
                $walletId = $payable->user->wallet->id;
                Transaction::create([
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => "پرداخت ناموفق رزرو کلاس وبینار ",
                    'wallet_id' => $walletId,
                    'status' => 'rejected',
                ]);
            }

            return redirect($redirect_url);
        }
        $ref = $response->referenceId();


        $payable = $order_payment->payable;
        if ($payable instanceof Order) {
            $payable->update([
                'payment_status' => 'paid',
                'status' => 'reserved',
                'seller_approval_status' => 'pending'
            ]);
            $walletId = $payable->student->user->wallet->id;

            Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'description' => "پرداخت موفق رزرو کلاس وبینار ",
                'wallet_id' => $walletId,
                'status' => 'approved',
            ]);
        }

        $order_payment->update([
            'ref_id' => $ref,
            'status' => 'paid',
        ]);


        return redirect($redirect_url);
    }


    public function pay_private_reservation_Wallet(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:buyer_addresses,id',
            'seller_id' => 'required|exists:users,id',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.category' => 'required|string',
            'code' => 'nullable|string',
            'payment_method' => 'required',
        ], [], [
            'address_id' => 'آدرس خریدار',
            'seller_id' => 'فروشنده',
            'items' => 'محصولات',
            'items.*.id' => 'شناسه محصول',
            'items.*.quantity' => 'تعداد محصول',
            'items.*.category' => 'دسته‌بندی محصول',
            'code' => 'کد تخفیف',
            'payment_method' => 'روش پرداخت',
        ]);

        $shipping_cost = $request->payment_method === 'post'
            ? SystemSetting::where('name', ShipingCost::post)->value('value')
            : SystemSetting::where('name', ShipingCost::tipax)->value('value');

        $user = auth()->user();
        $wallet = $user->wallet;
        $sellerId = $request->seller_id;
        $items = $request->items;
        $address = BuyerAddress::findOrFail($request->address_id);

        DB::beginTransaction();

        try {
            $totalPrice = 0;
            $discountableAmount = 0;
            $orderItems = [];

            // استخراج دسته‌بندی‌ها و نگاشت آن‌ها
            $categoryTitles = collect($items)->pluck('category')->unique();
            $categoryMap = Category::whereIn('title', $categoryTitles)->get()->keyBy('title');

            // بررسی تخفیف
            $discount = null;
            if (!empty($request->code)) {
                $categoryIds = $categoryMap->pluck('id')->toArray();

                $discount = DiscountCodeShop::where('code', $request->code)
                    ->where('seller_id', $sellerId)
                    ->where(function ($query) use ($categoryIds) {
                        $query->whereIn('category_id', $categoryIds)
                            ->orWhere('all', true);
                    })
                    ->first();

                if (!$discount || ($discount->expire_at && now()->greaterThan($discount->expire_at)) || $discount->usage_limit === 0) {
                    $discount = null;
                }
            }

            foreach ($items as $item) {
                $productId = $item['id'];
                $quantity = $item['quantity'];
                $categoryTitle = $item['category'];

                $ps = ProductSeller::where('product_id', $productId)
                    ->where('seller_id', $sellerId)
                    ->first();

                if (!$ps) {
                    throw new \Exception("محصول {$productId} برای این فروشنده یافت نشد.");
                }

                $price = $ps->price;
                if ($ps->discounted_price && $ps->discount_expire_at && now()->lt($ps->discount_expire_at)) {
                    $price = $ps->discounted_price;
                }

                $itemTotal = $price * $quantity;
                $totalPrice += $itemTotal;

                $isDiscountable = false;
                if ($discount) {
                    $itemCategory = $categoryMap[$categoryTitle] ?? null;
                    if ($discount->all || ($itemCategory && $discount->category_id == $itemCategory->id)) {
                        $isDiscountable = true;
                    }
                }

                if ($isDiscountable) {
                    $discountableAmount += $itemTotal;
                }

                $orderItems[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'total_price' => $itemTotal,
                ];
            }

            // اعمال تخفیف و محاسبه نهایی
            $discountAmount = $discount ? $discountableAmount * ($discount->parentage / 100) : 0;
            $finalPrice = $totalPrice - $discountAmount + $shipping_cost;

            if ($wallet->balance < $finalPrice) {
                DB::rollBack();
                return api_response([], 'موجودی شما برای پرداخت این سفارش کافی نمی‌باشد', 400);
            }

            $order = Order::create([
                'user_id' => $user->id,
                'seller_id' => $sellerId,
                'total_price' => $totalPrice,
                'final_price' => $finalPrice,
                'address' => $address->address,
                'buyer_address_id' => $address->id,
                'status' => 'pending',
                'payment_method' => $request->payment_method === 'post' ? 'پست' : 'تیپاکس',
                'sending_method' =>'درگاه پرداخت',
                'payment_status' => 'unpaid',
                'seller_approval_status' => 'pending',
                'order_code' => strtoupper(Str::random(10)),
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            // کاهش موجودی کیف پول
            $wallet->balance -= $finalPrice;
            $wallet->save();

            // ثبت تراکنش
            Transaction::create([
                'type' => 'payment',
                'amount' => $finalPrice,
                'description' => 'پرداخت خرید محصول',
                'wallet_id' => $wallet->id,
                'status' => 'approved',
            ]);

            // به‌روزرسانی وضعیت پرداخت
            $order->payment()->create([
                'user_id' => $user->id,
                'gateway_id' => 2,
                'amount' => $finalPrice,
                'authority' => crc32($user->id . '_' . now()->format('His')),
                'status' => 'paid',
                'description' => 'پرداخت رزرو خرید',
            ]);

            $order->update(['payment_status' => 'paid']);

            // کاهش محدودیت استفاده از کد تخفیف
            if ($discount) {
                $discount->decrement('usage_limit');
            }

            DB::commit();

            return api_response([], 'پرداخت با موفقیت انجام شد', 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'خطا در ثبت سفارش: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function checkCart(Request $request)
    {
        $request->validate([
            'seller_id' => 'required',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);
        $sellerId = $request->seller_id;
        $checkedItems = [];

        foreach ($request->items as $originalItem) {
            $errors = [];

            $productId = $originalItem['id'];
            $quantity = $originalItem['quantity'];
            $selectedSeller = $originalItem['selected_seller'] ?? [];

            $ps = ProductSeller::where('product_id', $productId)
                ->where('seller_id', $sellerId)
                ->first();

            if (!$ps) {
                $errors[] = [
                    'type' => 'deleted',
                    'massage' => 'این محصول توسط فروشنده حذف شده.',
                ];
            } else {
                if ($ps->quantity < $quantity) {
                    $errors[] = [
                        'type' => 'quantity',
                        'massage' => 'موجودی این محصول تغییر کرده.',
                    ];
                    $selectedSeller['stock'] = $ps->quantity;
                    $originalItem['quantity'] = $ps->quantity;
                }

                if ($ps->discount_expire_at && now()->gt($ps->discount_expire_at)) {
                    $errors[] = [
                        'type' => 'expired',
                        'massage' => 'تخفیف این محصول به پایان رسیده است.',
                    ];
                    $selectedSeller['discounted_price'] = $ps->price;
                    $selectedSeller['discount_percentage'] = 0;
                    $selectedSeller['discount_expire_at'] = null;
                }

                // چک قیمت تخفیف خورده
                if (isset($selectedSeller['discounted_price']) &&
                    $ps->discounted_price !== $selectedSeller['discounted_price']) {
                    $errors[] = [
                        'type' => 'discounted_price',
                        'massage' => 'قیمت تخفیف ‌خورده این محصول تغییر کرده است.',
                    ];
                    $selectedSeller['discounted_price'] = $ps->discounted_price;
                }

                // چک قیمت اصلی
                if (isset($selectedSeller['price']) &&
                    $ps->price !== $selectedSeller['price']) {
                    $errors[] = [
                        'type' => 'price',
                        'massage' => 'قیمت این محصول تغییر کرده است.',
                    ];
                    $selectedSeller['price'] = $ps->price;
                }
            }

            // بروزرسانی selected_seller در آیتم
            $updatedItem = $originalItem;
            $updatedItem['selected_seller'] = $selectedSeller;
            $updatedItem['errors'] = $errors;

            $checkedItems[] = $updatedItem;
        }

        $shipping_cost = SystemSetting::where('name', ShipingCost::post)->value('value');

        return api_response([
            'seller_id' => $request->seller_id,
            'items' => $checkedItems,
            'shipping_cost' => $shipping_cost ?? 0,
        ]);
    }

    public function checkDiscount(Request $request)
    {
        $request->validate([
            'seller_id' => 'required|exists:users,id',
            'code' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.category' => 'required|string',
            'items.*.selected_seller.discounted_price' => 'required|numeric|min:0',
        ]);

        $categories = Category::whereIn('title', collect($request->items)->pluck('category'))->pluck('id');

        $discount = DiscountCodeShop::where('code', $request->code)
            ->where('seller_id', $request->seller_id)
            ->where(function ($query) use ($categories) {
                $query->whereIn('category_id', $categories)
                    ->orWhere('all', true);
            })->first();

        if (!$discount) {
            return api_response([
                'valid' => false,
                'message' => 'کد تخفیف نامعتبر است یا برای این دسته‌ها نیست.'
            ]);
        }

        if ($discount->expire_at && now()->greaterThan($discount->expire_at)) {
            return api_response([
                'valid' => false,
                'message' => 'تاریخ انقضای کد تخفیف گذشته است.'
            ]);
        }

        if ($discount->usage_limit === 0) {
            return api_response([
                'valid' => false,
                'message' => 'محدودیت استفاده از این کد به پایان رسیده است.'
            ]);
        }

        $total = 0;
        $discountable = 0;

        foreach ($request->items as $item) {
            $itemTotal = $item['selected_seller']['discounted_price'] * $item['quantity'];
            $total += $itemTotal;

            $itemCategory = Category::where('title', $item['category'])->first();

            // فقط آیتم‌هایی که در دسته مجاز هستند تخفیف می‌گیرند
            if ($discount->all || ($itemCategory && $discount->category_id == $itemCategory->id)) {
                $discountable += $itemTotal;
            }
        }

        $discountAmount = $discountable * ($discount->parentage / 100);
        $finalTotal = $total - $discountAmount;

        return api_response([
            'total' => $total,
            'discountable_amount' => $discountable,
            'discount_percentage' => $discount->parentage,
            'discount_amount' => $discountAmount,
            'total_after_off' => $finalTotal,
        ]);
    }

    public function address(Request $request)
    {
        $user = auth()->user()->buyer;
        $address = BuyerAddress::where('buyer_id', $user->id)->get();
        $a = $address->map(function ($address) {
            return [
                'id' => $address->id,
                'title' => $address->title,
                'address' => $address->address,
            ];
        });
        return api_response($a);


    }



}
