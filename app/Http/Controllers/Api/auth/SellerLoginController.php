<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Models\contacts\role_value;
use App\Models\Seller\Seller;
use App\Models\TempUser;
use App\Models\User;
use App\Services\SmsServiceServices;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class SellerLoginController extends Controller
{
    public function otpLogin(Request $request)
    {
        $data = $request->validate([
            'grant_type' => 'required|in:otp_grant,password',
            'user' => 'required',
            'otp' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        $user = User::where('mobile', $data['user'])
            ->orWhere('email', $data['user'])
            ->first();

        if (!$user) {
            return api_response([], 'کاربری با این شماره یا ایمیل پیدا نشد. ابتدا ثبت ‌نام کنید.', 404);
        }

        if ($data['grant_type'] === 'otp_grant') {
            if (empty($data['otp'])) {
                return api_response([], 'کد تایید ارسال نشده است.', 422);
            }

            if (!$user->sms_sent_date || Carbon::parse($user->sms_sent_date)->diffInMinutes(now()) > 2) {
                return api_response([], 'کد تایید منقضی شده است. لطفاً دوباره درخواست ارسال کد بدهید.', 401);
            }

            if ($user->sms_sent_code !== $data['otp']) {
                return api_response([], 'کد تایید اشتباه است.', 401);
            }
        }

        if ($data['grant_type'] === 'password') {
            if (empty($data['password'])) {
                return api_response([], 'رمز عبور ارسال نشده است.', 422);
            }

            if (!Hash::check($data['password'], $user->password)) {
                return api_response([], 'رمز عبور اشتباه است.', 401);
            }
        }

        Auth::login($user);

        if (!$user->seller) {
            return api_response([], 'دسترسی فقط برای فروشندگان مجاز است.', 403);
        }

        $accessToken = $user->createToken('authToken')->plainTextToken;

        $userDataWithToken = $user->toArray();
        $userDataWithToken['token'] = [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
        ];

        return api_response($userDataWithToken);
    }
    public function simpleRegister(Request $request)
    {
        $data = $request->validate([
            'user'     => 'required|string|max:255|unique:users,mobile|unique:users,email',
            'password' => 'required',
            'otp'      => 'required',
        ]);

        DB::beginTransaction();

        $userData = [
            'password' => bcrypt($data['password']),
        ];

        // تشخیص نوع ورودی (موبایل یا ایمیل)
        if (filter_var($data['user'], FILTER_VALIDATE_EMAIL)) {
            $userData['email'] = $data['user'];
            $tempUser = TempUser::firstOrCreate(
                ['email' => $data['user']],
                ['sms_sent_code' => $data['otp']]
            );
        } else {
            $userData['mobile'] = $data['user'];
            $tempUser = TempUser::firstOrCreate(
                ['mobile' => $data['user']],
                ['sms_sent_code' => $data['otp']]
            );
        }

        // بررسی کد تایید
        if ($tempUser->sms_sent_code !== $data['otp']) {
            DB::rollBack();
            return api_response([], 'کد یکبار مصرف نادرست است یا منقضی شده است', 401);
        }

        $userData['mobile_verified_at'] = now();
        $newUser = User::create($userData);
        $newUser->assignRole(role_value::seller);
        event(new Registered($newUser));

        Auth::login($newUser);

        Seller::create([
            'user_id' => $newUser->id,
            'mobile'  => $newUser->mobile ?? null,
            'email'   => $newUser->email ?? null,
        ]);

        $accessToken = $newUser->createToken('authToken')->plainTextToken;

        $userDataWithToken = $newUser->toArray();
        $userDataWithToken['token'] = [
            'token_type'   => 'Bearer',
            'access_token' => $accessToken,
        ];
        $userDataWithToken['is_admin'] = $newUser->hasRole('adminController');

        $smsService = new SmsServiceServices();

        $n = $newUser->mobile;
        $d =   $smsService->sendPatternSms(
            '3iw7cuvluwbaazn',
            $n,
            [
                'name' =>$newUser->getFullName(),
                'link' => 'https://seller.lingomasters.ir/auth/login',
            ]
        );

        DB::commit();

        return api_response($userDataWithToken, 'ثبت‌نام با موفقیت انجام شد.');
    }

    public function checkUserExists(Request $request)
    {
        $request->validate([
            'user' => 'required|string',
        ]);

        $user = User::where('email', $request->input('user'))
            ->orWhere('mobile', $request->input('user'))
            ->first();

        if ($user) {
            if (!$user->hasRole(role_value::seller)) {
                return api_response([
                    'exists' => false,
                ], 'این شماره یا ایمیل قبلاً در بخش دیگری ثبت‌نام شده است و مجاز به ثبت‌نام به عنوان فروشنده نیست.', 400);
            }

            return api_response([
                'exists' => true,
            ], 'کاربر با این ایمیل یا شماره موبایل قبلاً ثبت‌نام کرده است.');
        }

        return api_response([
            'exists' => false,
        ], 'کاربری با این ایمیل یا شماره موبایل وجود ندارد.');
    }



}
