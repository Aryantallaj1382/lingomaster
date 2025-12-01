<?php

namespace App\Http\Controllers\Api\seller;

use App\Http\Controllers\Controller;
use App\Models\Financial\Transaction;
use App\Models\Financial\Wallet;
use App\Models\order\Order;
use App\Models\SellerAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Morilog\Jalali\Jalalian;

class SellerWalletController extends Controller
{

    public function show()
    {
        $user = auth()->user();

        $wallet = $user->wallet ?? $user->wallet()->create([
            'balance' => 0,
        ]);

        $totalDeliveredEarnings = Order::where('seller_id', $user->seller->id)
            ->where('status', 'delivered')
            ->sum('final_price');
        $tasvie = Transaction::whereRelation('wallet', 'user_id', $user)
            ->where('type', 'withdraw')->where('status', 'approved')->sum('amount');
        $dar_tasvie = Transaction::whereRelation('wallet', 'user_id', $user)
            ->where('type', 'withdraw')->where('status', 'pending')->sum('amount');

        return api_response([
            'balance' => $wallet->balance,
            'delivered' => $wallet->balance - ($wallet->balance * 0.25),
            'tasvie' => $tasvie,
            'dar_tasvie' => $dar_tasvie,
            'maliat' => $wallet->balance * 0.25,
        ]);
    }


    public function taraconesh(Request $request)
    {
        $user = auth()->user()->seller;

        $query = Order::where('seller_id', $user->id);

        // فیلتر بر اساس تاریخ شروع
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        // فیلتر بر اساس تاریخ پایان
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // فیلتر بر اساس وضعیت پرداخت
        if ($request->filled('status')) {
            $query->where('payment_status', $request->status);
        }

        $order = $query->latest()->paginate(); // مرتب‌سازی بر اساس جدیدترین

        $startIndex = ($order->currentPage() - 1) * $order->perPage() + 1;

        $order->getCollection()->transform(function ($order, $index) use ($startIndex) {
            return [
                'row' => $startIndex + $index,
                'created_at' => $order->created_at,
                'price' => $order->final_price,
                'final_price' => $order->final_price * 25 / 100,
                'status' => $order->payment_status,
            ];
        });

        return api_response($order);
    }

    public function darkhast(Request $request)
    {
        $user = auth()->user();

        $query = Transaction::whereRelation('wallet', 'user_id', $user->id)
            ->where('type', 'withdraw');

        // فیلتر از تاریخ
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        // فیلتر تا تاریخ
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // فیلتر بر اساس وضعیت
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $transaction = $query->latest()->paginate();

        $startIndex = ($transaction->currentPage() - 1) * $transaction->perPage() + 1;

        $transaction->getCollection()->transform(function ($transaction, $index) use ($startIndex) {
            return [
                'row' => $startIndex + $index,
                'created_at' => $transaction->created_at,
                'status' => $transaction->status,
                'price' => $transaction->amount,
            ];
        });

        return api_response($transaction);
    }


    public function withdraw()
    {

        $seller = auth()->user()->seller;
        $account = SellerAccount::where('seller_id', $seller->id)->where('is_default' , 1)->first();
        if (!$account) {
            return api_response([],'یک شماره حساب پیش فرض در بخش مالی انتخاب کنید');
        }
        $wallet = Wallet::where('user_id', auth()->id())->first();

        $totalDeliveredEarnings = Order::where('seller_id', $seller->id)
            ->where('status', 'delivered')
            ->sum('final_price');

        $wallet->transactions()->create([
            'type' => 'withdraw',
            'amount' =>$totalDeliveredEarnings,
            'status' => 'pending',
            'account_number' => $account->shaba,
            'account_owner_name' => $seller->name,
        ]);
        $a = 'درخوایت بردشت ثبت شد بعد از تایید پول به حساب پیش فرض شما واریز میشود';
        return api_response([], $a);

    }
    public function getRevenue($sellerId, Request $request)
    {
        $filter = $request->query('filter', 'daily');  // daily, weekly, monthly, yearly
        $month = $request->query('month', null);      // عدد 1 تا 12
        $categoryId = $request->query('category_id', null);

        $result = $this->calculateSellerRevenue($sellerId, $filter, $month, $categoryId);

        return api_response($result);
    }

    protected function calculateSellerRevenue($sellerId, $filter, $month, $categoryId)
    {
        $query = Order::with('items.product.category')
            ->where('seller_id', $sellerId)
            ->where('payment_status', 'paid');

        if ($month) {
            $query->whereMonth('created_at', $month);
        }

        if ($categoryId) {
            $query->whereHas('items.product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $orders = $query->get();

        $revenueByPeriod = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $createdAt = $order->created_at;

                switch ($filter) {
                    case 'daily':
                        $key = (int)$createdAt->format('d');  // روز به عدد 1 تا 31
                        break;
                    case 'weekly':
                        $key = (int)$createdAt->weekOfYear; // هفته سال
                        // اگر فقط 4 هفته مد نظره می‌تونیم اصلاح کنیم پایین‌تر
                        $key = min($key, 4); // حداکثر 4 هفته
                        break;
                    case 'monthly':
                        $key = (int)$createdAt->format('m'); // ماه 1 تا 12
                        break;
                    case 'yearly':
                        $key = (int)$createdAt->format('Y'); // سال کامل
                        break;
                    default:
                        $key = (int)$createdAt->format('d');
                }

                if (!isset($revenueByPeriod[$key])) {
                    $revenueByPeriod[$key] = 0;
                }

                $revenueByPeriod[$key] += $item->total_price;
            }
        }

        // حالا آرایه نهایی که کل روزها / هفته ها / ماه ها / سال ها رو نشون بده و مقدار خالی رو صفر بزاره
        $finalRevenue = [];

        switch ($filter) {
            case 'daily':
                for ($day = 1; $day <= 30; $day++) {
                    $finalRevenue[$day] = $revenueByPeriod[$day] ?? 0;
                }
                break;
            case 'weekly':
                for ($week = 1; $week <= 4; $week++) {
                    $finalRevenue[$week] = $revenueByPeriod[$week] ?? 0;
                }
                break;
            case 'monthly':
                for ($monthNum = 1; $monthNum <= 12; $monthNum++) {
                    $finalRevenue[$monthNum] = $revenueByPeriod[$monthNum] ?? 0;
                }
                break;
            case 'yearly':
                // برای سال‌ها همه سال‌هایی که در داده‌ها هستن رو بگیر
                $years = array_keys($revenueByPeriod);
                sort($years);
                foreach ($years as $year) {
                    $finalRevenue[$year] = $revenueByPeriod[$year];
                }
                break;
            default:
                for ($day = 1; $day <= 30; $day++) {
                    $finalRevenue[$day] = $revenueByPeriod[$day] ?? 0;
                }
                break;
        }

        return $finalRevenue;
    }

}
