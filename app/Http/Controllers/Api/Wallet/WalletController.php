<?php

namespace App\Http\Controllers\Api\Wallet;

use App\Http\Controllers\Controller;
use App\Models\Financial\Transaction;
use App\Models\Financial\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    // متد برای واریز پول به کیف پول
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $wallet = auth()->user()->wallet;

        if (!$wallet) {
            return response()->json(['error' => 'User wallet not found'], 404);
        }

        DB::beginTransaction();

        try {
            $newBalance = $wallet->balance + $request->amount;

            // ایجاد تراکنش واریز با استفاده از مدل
            $transaction = $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $request->amount,
                'balance_after' => $newBalance,
                'description' => 'Deposit to wallet',
            ]);

            // بروزرسانی موجودی کیف پول با استفاده از مدل
            $wallet->update(['balance' => $newBalance]);

            // Commit تراکنش
            DB::commit();

            return response()->json([
                'message' => 'Deposit successful',
                'new_balance' => $newBalance,
                'transaction' => $transaction,
            ], 200);
        } catch (\Exception $e) {
            // در صورت بروز خطا، تراکنش را لغو کرده و پیام خطا باز می‌گردد
            DB::rollBack();
            return response()->json([
                'error' => 'An error occurred while processing the deposit',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    // متد برای دریافت لیست کامل تراکنش‌ها برای یک کیف پول
    public function getTransactions($walletId)
    {
        // بررسی موجودیت کیف پول با استفاده از مدل
        $wallet = Wallet::findOrFail($walletId);

        // دریافت تراکنش‌ها از مدل Transaction برای این کیف پول
        $transactions = $wallet->transactions()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'wallet_id' => $walletId,
            'transactions' => $transactions,
        ], 200);
    }

    // متد برای مشاهده جزئیات یک تراکنش خاص
    public function getTransactionDetails($transactionId)
    {
        // یافتن تراکنش با استفاده از مدل
        $transaction = Transaction::findOrFail($transactionId);

        return response()->json([
            'transaction' => $transaction,
        ], 200);
    }

    // متد برای انتقال وجه بین کیف پول‌ها
    public function transfer(Request $request)
    {
        $request->validate([
            'from_wallet_id' => 'required|exists:wallets,id',
            'to_wallet_id' => 'required|exists:wallets,id|different:from_wallet_id', // کیف پول مقصد نباید همان کیف پول فرستنده باشد
            'amount' => 'required|numeric|min:1',
        ]);

        // پیدا کردن کیف پول‌ها با استفاده از مدل
        $fromWallet = Wallet::findOrFail($request->from_wallet_id);
        $toWallet = Wallet::findOrFail($request->to_wallet_id);

        // بررسی موجودی کیف پول فرستنده
        if ($fromWallet->balance < $request->amount) {
            return response()->json(['error' => 'Insufficient funds'], 400);
        }

        // شروع تراکنش
        DB::beginTransaction();

        try {
            // مقدار جدید کیف پول‌ها بعد از انتقال
            $newFromWalletBalance = $fromWallet->balance - $request->amount;
            $newToWalletBalance = $toWallet->balance + $request->amount;

            // ایجاد تراکنش‌های واریز و برداشت با استفاده از Eloquent
            $walletTransfer = WalletTransfer::create([
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'amount' => $request->amount,
                'status' => 'completed', // وضعیت انتقال (در اینجا 'completed')
            ]);

            $fromTransaction = $fromWallet->transactions()->create([
                'type' => 'withdraw',
                'amount' => $request->amount,
                'balance_after' => $newFromWalletBalance,
                'description' => 'Withdrawal from wallet for transfer',
            ]);

            $toTransaction = $toWallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $request->amount,
                'balance_after' => $newToWalletBalance,
                'description' => 'Deposit to wallet from transfer',
            ]);

            // بروزرسانی موجودی کیف پول‌ها با استفاده از مدل
            $fromWallet->update(['balance' => $newFromWalletBalance]);
            $toWallet->update(['balance' => $newToWalletBalance]);

            // Commit تراکنش
            DB::commit();

            return response()->json([
                'message' => 'Transfer successful',
                'from_wallet_balance' => $newFromWalletBalance,
                'to_wallet_balance' => $newToWalletBalance,
                'wallet_transfer' => $walletTransfer,
            ], 200);
        } catch (\Exception $e) {
            // در صورت بروز خطا، تراکنش را لغو کرده و پیام خطا باز می‌گردد
            DB::rollBack();
            return response()->json([
                'error' => 'An error occurred while processing the transfer',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
