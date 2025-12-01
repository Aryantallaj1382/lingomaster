<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\TempUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use IPPanel\Client;
use Throwable;


class SmsController extends Controller
{
    public function sendOtp(Request $request)
    {

        $data = $request->validate([
            'mobile' => 'nullable|regex:/^[0-9]{10,15}$/',
            'email' => 'nullable|email',
            'user' => 'nullable',
        ], [
            'mobile.regex' => 'شماره موبایل باید معتبر باشد',
            'email.email' => 'ایمیل باید معتبر باشد',
            'user' => 'ایمیل یا موبایل'
        ]);

        if (empty($data['mobile']) && empty($data['email']) && empty($data['user'])) {
            return response()->json([
                'message' => 'حداقل یکی از فیلدهای شماره موبایل یا ایمیل باید پر شود'
            ], 400);
        }

        $identifier = $data['user'] ?? $data['mobile'] ?? $data['email'];
        $isEmail = Str::contains($identifier, '@');

        if (!$isEmail && (!is_numeric($identifier) || strlen($identifier) !== 11)) {
            return response()->json([
                'message' => 'شماره موبایل باید ۱۱ رقم باشد.'
            ], 422);
        }
        $user = User::where('email', $identifier)->orWhere('mobile', $identifier)->first();

        if (blank($user)) {
            $user = TempUser::firstOrCreate([
                $isEmail ? 'email' : 'mobile' => $identifier,
            ], [
                'sms_sent_tries' => 0,
                'sms_sent_code' => null,
            ]);
        }

        $seconds = optional($user->sms_sent_date)->diffInSeconds(now());
        $remaining = round(120 - $seconds);
        if ($seconds && $seconds < 120) {
            $remaining = round(120 - $seconds); // گرد کردن به عدد صحیح
            return api_response([
                'remain' => $remaining],
                 "لطفاً پس از گذشت " . $remaining . " ثانیه دوباره تلاش کنید."
            , 429);
        }

        $code = random_int(100000, 999999);

        $user->update([
            'sms_sent_tries' => 0,
            'sms_sent_date' => now(),
            'sms_sent_code' => $code,
        ]);

        $responseMessages = [
            'remain' => $remaining,

        ];

        try {
            if (!empty($data['mobile'])) {
                (new Client(config('app.sms_panel_apikey')))
                    ->sendPattern(
                        'i5ptms6ccqcs3e5',
                        '3000505',
                        $data['mobile'],
                        ['code' => $code]
                    );
                $responseMessages['message'] = 'کد تایید به شماره موبایل ارسال شد.';

            }

            if (!empty($data['email'])) {
                Mail::to($data['email'])->send(new OtpMail($user));
                $responseMessages['email'] = 'کد تایید به ایمیل ارسال شد.';
            }

            if (!empty($data['user']) && !$isEmail) {
                (new Client(config('app.sms_panel_apikey')))
                    ->sendPattern(
                        'i5ptms6ccqcs3e5',
                        '3000505',
                        $data['user'],
                        ['code' => $code]
                    );
                $responseMessages['message'] = 'کد تایید به شماره موبایل ارسال شد.';
            }

            if (!empty($data['user']) && $isEmail) {
                Mail::to($data['user'])->send(new OtpMail($user));
                $responseMessages['user'] = 'کد تایید به ایمیل ارسال شد.';
            }

        } catch (Throwable $e) {

            return response()->json([
                'message' => $e
            ], 500);
        }
        return api_response($responseMessages, 'کد تایید برای شما ارسال شد');
    }



}
