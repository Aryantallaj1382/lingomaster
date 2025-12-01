<?php

namespace App\Http\Controllers;

use App\Helpers\zarinpal;
use App\Models\contacts\api;
use App\Models\Financial\Payment;
use App\Models\Financial\Transaction;
use App\Models\Financial\Wallet;
use App\Models\Professors\WorkShopReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletController extends Controller
{
    public function show()
    {
        $user = auth()->user();

        $wallet = $user->wallet ?? $user->wallet()->create([
            'balance' => 0,

        ]);

        return api_response([
            'balance' => $wallet->balance,
            'Inactive_balance' => 0,
            'Fine' => 0,
            'all' => $wallet->balance,
        ]);
    }

    public function ini(Request $request)
    {
        $input = $request->input('type');
        $date = $request->input('date');
        $search = $request->input('search');
        $status = $request->input('status');

        $user = auth()->user();
        $wallet = $user->wallet;

        if ($input == 'payment') {
            $transactions = $wallet->transactions()->where('type', 'payment')
                ->where('status', 'approved')
                ->when($date, function ($query) use ($date) {
                    $query->whereDate('created_at', $date);
                })->when($date, function ($query) use ($search) {
                    $query->where('description', 'like', '%' . $search . '%');
                })
                ->latest()->paginate();

            $transactions->getCollection()->transform(function ($transaction, $key) use ($transactions) {
                return [
                    'row_number' => $transactions->firstItem() + $key, // شماره ردیف
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'type' => $transaction->type,
                    'url' => $transaction->url,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'date' => $transaction->created_at->format('Y-m-d'),
                    'time' => $transaction->created_at->format('H:i'),
                ];
            });


            return api_response(
                $transactions,
            );
        }
        if ($input == 'deposit') {
            $transactions = $wallet->transactions()->where('type', 'deposit')
                ->when($date, function ($query) use ($date) {
                    $query->whereDate('created_at', $date);
                })->when($date, function ($query) use ($search) {
                    $query->where('description', 'like', '%' . $search . '%');
                })
                ->when($date, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->latest()->paginate();
            $transactions->getCollection()->transform(function ($transaction, $key) use ($transactions) {
                return [
                    'row_number' => $transactions->firstItem() + $key, // شماره ردیف
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('Y-m-d'),
                    'time' => $transaction->created_at->format('H:i'),
                ];
            });

            return api_response(
                $transactions,
            );
        }
        if ($input == 'withdraw') {
            $transactions = $wallet->transactions()->where('type', 'withdraw')
                ->when($date, function ($query) use ($date) {
                    $query->whereDate('created_at', $date);
                })->when($date, function ($query) use ($search) {
                    $query->where('description', 'like', '%' . $search . '%');
                })
                ->when($date, function ($query) use ($status) {
                    $query->where('status', $status);
                })->paginate();

            $transactions->getCollection()->transform(function ($transaction, $key) use ($transactions) {
                return [
                    'row_number' => $transactions->firstItem() + $key, // شماره ردیف
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'account_number' => $transaction->account_number,
                    'account_owner_name' => $transaction->account_owner_name,
                    'status' => $transaction->status,
                    'date' => $transaction->created_at->format('Y-m-d'),
                    'time' => $transaction->created_at->format('H:i'),
                ];
            });


            return api_response(
                $transactions,
            );
        }
        if ($input == 'lingo') {
            $transactions = $wallet->transactions()->where('type', 'lingo')
                ->where('status', 'approved')
                ->when($date, function ($query) use ($date) {
                    $query->whereDate('created_at', $date);
                })->when($date, function ($query) use ($search) {
                    $query->where('description', 'like', '%' . $search . '%');
                })
                ->latest()->paginate();
            $transactions->getCollection()->transform(function ($transaction, $key) use ($transactions) {
                return [
                    'row_number' => $transactions->firstItem() + $key, // شماره ردیف
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'title' => $transaction->title,
                    'description' => $transaction->description,
                    'date' => $transaction->created_at->format('Y-m-d'),
                    'time' => $transaction->created_at->format('H:i'),
                ];
            });

            return api_response($transactions);
        }
        if ($input == 'income') {
            $transactions = $wallet->transactions()->where('type', 'income')
                ->when($date, function ($query) use ($date) {
                    $query->whereDate('created_at', $date);
                })->when($date, function ($query) use ($search) {
                    $query->where('description', 'like', '%' . $search . '%');
                })
                ->when($date, function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->latest()->paginate(10);
            $transactions->getCollection()->transform(function ($transaction, $key) use ($transactions) {
                return [
                    'row_number' => $transactions->firstItem() + $key, // شماره ردیف
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'date' => $transaction->created_at->format('Y-m-d'),
                    'time' => $transaction->created_at->format('H:i'),
                ];
            });

            return api_response(
                $transactions,
            );
        }

        return api_response([]);
    }

    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000',
        ]);

        $user = auth()->user();

        // اگر کیف پول وجود نداشت، ایجادش کن
        $wallet = $user->wallet ?? $user->wallet()->create(['balance' => 0]);

        $amount = $request->amount;
        $description = 'شارژ کیف پول';

        // ساخت تراکنش در حالت "در انتظار پرداخت"
        $transaction = $wallet->transactions()->create([
            'amount' => $amount,
            'type' => 'deposit',
            'status' => 'pending',
            'description' => $description,
        ]);

        $paymentService = new zarinpal();
        $callback = 'https://api.lingomasters.ir/zarin_callback_wallet';

        $response = $paymentService->pay($transaction, $amount, $description, $callback);
        $transaction->update([
            'authority' => $response['authority'],
        ]);

        return ['url' => $response['url']];
    }

    public function zarin_callback_wallet(Request $request)
    {
        if (!$request->filled('Authority') || !$request->filled('Status')) {
            return api_response([], 'خطا در پاسخ درگاه پرداخت', 403);
        }

        $transaction = Transaction::where('authority', $request->Authority)->first();

        if (blank($transaction)) {
            return api_response([], 'تراکنش مورد نظر پیدا نشد', 403);
        }

        $amount = $transaction->amount;
        $authority = $transaction->authority;

        // بررسی وضعیت پرداخت در زرین‌پال
        $response = zarinpal()
            ->merchantId(config('zarinpal.merchant_id'))
            ->amount($amount)
            ->verification()
            ->authority($authority)
            ->send();

        $redirect_url = 'https://lingomasters.ir/wallet?backFrom=payment';

        if (!$response->success()) {
            $transaction->update(['status' => 'rejected']);
            return redirect($redirect_url);
        }

        // اگر موفق بود:
        $ref = $response->referenceId();
        $wallet = $transaction->wallet;

        if ($wallet instanceof Wallet) {
            DB::beginTransaction();
            try {
                // شارژ کیف پول
                $wallet->increment('balance', $amount);

                // آپدیت خود تراکنش قبلی
                $transaction->update([
                    'balance_after' => $wallet->balance,
                ]);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                return redirect($redirect_url);
            }
        }

        return redirect($redirect_url);
    }

    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'shaba' => 'required',
        ]);
        $wallet = Wallet::where('user_id', auth()->id())->first();

        if ($request->amount > $wallet->balance) {
            return api_response([], 'موجودی شما کمتر از مبلغ درخواستی است');
        }
        $new = $wallet->balance - $request->amount;
        $wallet->update([
            'balance' => $new,
        ]);
        $wallet->transactions()->create([
            'type' => 'withdraw',
            'amount' => $request->amount,
            'status' => 'pending',
            'account_number' => $request->shaba,
            'account_owner_name' => auth()->user()->professor->full_name(),
        ]);
        $a = 'درخوایت بردشت ثبت شد بعد از تایید پول به حساب شما واریز میشود';
        return api_response([], $a);

    }
}
