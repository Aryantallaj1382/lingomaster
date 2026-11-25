<?php

namespace App\Http\Controllers\Api\Students\ExamPayments;

use App\Helpers\zarinpal;
use App\Http\Controllers\Controller;
use App\Models\Exam_question\Exam;
use App\Models\ExamPayment\ExamPayment;
use App\Models\ExamPayment\OrderExamPayment;
use App\Models\Financial\Payment;
use App\Models\Financial\Transaction;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

class ExamPaymentController extends Controller
{


    public function index()
    {
        $ExamPayment = ExamPayment::all()->select(['name', 'id', 'image']);
        return api_response($ExamPayment);
    }

    public function showExamPayment(int $id)
    {
        $ExamPayment = ExamPayment::findOrFail($id);
        $dolar = SystemSetting::where('name', 'dolar_price')->value('value');
        $totalToman = $ExamPayment->DollarPrice * $dolar;
        $total = $totalToman + $ExamPayment->tax + $ExamPayment->wage;
        return api_response([
            'image' => $ExamPayment->image,
            'name' => $ExamPayment->name,
            'DollarPrice' => $ExamPayment->DollarPrice,
            'totalToman' => $totalToman,
            'tax' => $ExamPayment->tax,
            'wage' => $ExamPayment->wage,
            'total' => $total,
        ]);

    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required',
            'exam_location' => 'required|string|max:255',
            'exam_date' => 'required|date|after_or_equal:today',
            'exam_time' => 'required|date', // تاریخ کامل چون نوع فیلد datetime است
            'description' => 'nullable|string|max:1000',
            'exam_payment_id' => 'required|exists:exam_payments,id',
        ]);

        $data['user_id'] = auth()->id();

        $data['exam_date'] = Carbon::parse($data['exam_date'])->format('Y-m-d H:i:s');
        $data['exam_time'] = Carbon::parse($data['exam_time'])->format('Y-m-d H:i:s');
        $OrderExamPayment = OrderExamPayment::create($data);

        return api_response(['id' => $OrderExamPayment->id], 'درخواست ثبت شد', 201);
    }


    public function showOrderExamPayment(int $id)
    {
        $OrderExamPayment = OrderExamPayment::findOrFail($id);

        $api = [
            'id' => $OrderExamPayment->id,
            'user_id' => $OrderExamPayment->user_id,
            'exam_location' => $OrderExamPayment->exam_location,
            'exam_date' => $OrderExamPayment->exam_date,
            'exam_time' => $OrderExamPayment->exam_time,
            'description' => $OrderExamPayment->description,
            'full_name' => $OrderExamPayment->user->first_name . ' ' . $OrderExamPayment->user->last_name,
            'email' => $OrderExamPayment->user->email,
            'mobile' => $OrderExamPayment->user->mobile,
        ];

        return api_response($api);

    }

    public function pay_group_reservation(Request $request, $id)
    {

        $order = OrderExamPayment::findOrFail($id);
        $pay = $order->examPayment;
        $dollarRate = SystemSetting::where('name', 'dolar_price')->value('value');
        $totalToman = $pay->DollarPrice * $dollarRate;
        $total = $totalToman + $pay->tax + $pay->wage;


        $description = "پرداخت آزمون پرداخت ";

        $paymentService = new zarinpal();
        $callback = 'https://api.lingomasters.ir/zarin_callback_exam';
        return $paymentService->pay($order, $total, $description, $callback);
    }


    public function zarin_callback_exam(Request $request)
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

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $redirect_url = 'https://lingomasters.ir';

            $payable = $order_payment->payable;
            if ($payable instanceof OrderExamPayment) {
                $payable->update([
                    'status' => 'failed',
                ]);
                $walletId = $payable->user->wallet->id;
                Transaction::create([
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => "پرداخت ناموفق آژمون پرداخت ",
                    'wallet_id' => $walletId,
                    'status' => 'rejected',
                ]);
            }

            return redirect($redirect_url);
        }


        $payable = $order_payment->payable;
        if ($payable instanceof OrderExamPayment) {
            $payable->update([
                'status' => 'paid',
            ]);
            $redirect_url = 'https://lingomasters.ir/exams/pay/success';

            $walletId = $payable->user->wallet->id;

            Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'description' => "پرداخت موفق آژمون پرداخت ",
                'wallet_id' => $walletId,
                'status' => 'approved',
            ]);
        }

        $order_payment->update([
            'status' => 'paid',
        ]);


        return redirect($redirect_url);
    }


    public function wallet(Request $request, $id)
    {

        $wallet = auth()->user()->wallet;
        $order = OrderExamPayment::findOrFail($id);
        $pay = $order->examPayment;
        $dollarRate = SystemSetting::where('name', 'dolar_price')->value('value');
        $totalToman = $pay->DollarPrice * $dollarRate;
        $total = $totalToman + $pay->tax + $pay->wage;

        $user_id = auth()->user()->id;

        $now = now()->format('His'); // ساعت به صورت 143251
        $rawString = $order->id . '_' . $user_id . '_' . $now;

        $authority = crc32($rawString); // خروجی عدد صحیح


        $description = "پرداخت آزمون پرداخت";

        $pa = $order->payment()->create([
            'user_id' => auth()->id(),
            'gateway_id' => 2,
            'amount' => $total,
            'authority' => $authority,
            'status' => 'pending',
            'description' => $description,
        ]);
        $ta = Transaction::create([
            'type' => 'payment',
            'amount' => $total,
            'description' => $description,
            'wallet_id' => $wallet->id,
            'status' => 'Pending',
        ]);
        if ($wallet->balance > $total) {
            $wallet->balance -= $total;
            $wallet->save();
            $ta->update([
                'status' => 'approved',
            ]);
            $pa->update([
                'status' => 'paid',
            ]);
            $order->update([
                'status' => 'paid',
            ]);
            return api_response([], 'پرداخت انجام شد');

        } else {
            $ta->update([
                'status' => 'rejected',
            ]);
            $pa->update([
                'status' => 'failed',
            ]);

            return api_response([], ' موجودی شما برای پرداخت  کافی نمیباشد', 400);
        }


    }


}
