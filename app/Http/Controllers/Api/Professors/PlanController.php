<?php

namespace App\Http\Controllers\Api\Professors;

use App\Helpers\zarinpal;
use App\Http\Controllers\Controller;

use App\Models\Financial\Payment;
use App\Models\Financial\Transaction;
use App\Models\Plan;
use App\Models\PlanDuration;
use App\Models\ProfessorPlanSubscription;
use App\Models\Professors\WebinarReservation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


class  PlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = Plan::with('durations')->get()->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'features' => $plan->features,
                'durations' => $plan->durations->map(function ($duration) {
                    return [
                        'months' => $duration->months,
                        'id' => $duration->id,
                        'price' => (int)$duration->price,
                        'discount_percent' => $duration->discount_percent,
                    ];
                }),
            ];
        });
        return api_response($plans);
    }

    public function pay_private_reservation_Wallet(Request $request)
    {
        $request->validate([
            'plan_id' => 'required',
            'language_id' => 'required',
            'plan_duration_id' => 'required',
        ]);
        $plan_duration = PlanDuration::find($request->plan_duration_id);
        $start_date = now();
        $end_date = now()->addMonths($plan_duration->months);
        $wallet = auth()->user()->wallet;
        $professor_id = auth()->user()->professor->id;

        $now = now()->format('His'); // ساعت به صورت 143251
        $rawString = $request->plan_id . '_' . $professor_id . '_' . $now;

        $authority = crc32($rawString); // خروجی عدد صحیح


        $amount = $plan_duration->price;
        $description = "پرداخت خرید پلن ";

        $pa = $plan_duration->payment()->create([
            'user_id' => auth()->id(),
            'gateway_id' => 2,
            'amount' => $amount,
            'authority' => $authority,
            'status' => 'pending',
            'description' => $description,
        ]);
        $ta = Transaction::create([
            'type' => 'payment',
            'amount' => $amount,
            'description' => $description,
            'wallet_id' => $wallet->id,
            'status' => 'Pending',
        ]);
        if ($wallet->balance > $amount) {
            $wallet->balance -= $amount;
            $wallet->save();
            $ta->update([
                'status' => 'approved',
            ]);
            $pa->update([
                'status' => 'paid',
            ]);
            ProfessorPlanSubscription::create([
                'plan_id' => $request->plan_id,
                'professor_id' => $professor_id,
                'plan_duration_id' => $request->plan_duration_id,
                'language_id' => $request->language_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => 'Pending',

            ]);


            return api_response([], 'پرداخت انجام شد و کلاس برای شما با موفقیت رزرو شد');

        } else {
            $ta->update([
                'status' => 'rejected',
            ]);
            $pa->update([
                'status' => 'failed',
            ]);

            return api_response([], 'موجودی شما برای پرداخت این کلاس کافی نمیباشد');
        }


    }


    public function pay_group_reservation(Request $request)
    {
        $request->validate([
            'plan_id' => 'required',
            'language_id' => 'required',
            'plan_duration_id' => 'required',
        ]);
        $plan_duration = PlanDuration::find($request->plan_duration_id);
        $start_date = now();
        $end_date = now()->addMonths($plan_duration->months);
        $professor_id = auth()->user()->professor->id;
        $reservation = ProfessorPlanSubscription::firstOrCreate([
            'plan_id' => $request->plan_id,
            'professor_id' => $professor_id,
            'plan_duration_id' => $request->plan_duration_id,
            'language_id' => $request->language_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'status' => 'Pending',

        ]);



        $amount = $plan_duration->price;
        $description = "پرداخت رزرو پلن: " ;

        $paymentService = new zarinpal();
        $callback = 'https://api.lingomasters.ir/zarin_callback_plan';
        return $paymentService->pay($reservation, $amount, $description, $callback);
    }

    public function  zarin_callback_plan(Request $request)
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
        $redirect_url = 'https://professor.lingomasters.ir/wallet';

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $payable = $order_payment->payable;
            if ($payable instanceof ProfessorPlanSubscription) {
                $payable->update([
                    'status' => 'failed',
                ]);
                $walletId = $payable->profesor->user->wallet->id;

                Transaction::create([
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => "پرداخت ناموفق خرید پلن ",
                    'wallet_id' => $walletId,
                    'status' => 'rejected',
                ]);
            }

            return redirect($redirect_url);
        }
        $ref = $response->referenceId();


        $payable = $order_payment->payable;
        if ($payable instanceof ProfessorPlanSubscription) {
            $payable->update([
                'status' => 'paid'
            ]);
            $walletId = $payable->profesor->user->wallet->id;

            Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'description' => "پرداخت ناموفق خرید پلن ",
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


}
