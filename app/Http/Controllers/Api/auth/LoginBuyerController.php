<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Models\Buyer\Buyer;
use App\Models\contacts\role_value;
use App\Models\TempUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class LoginBuyerController extends Controller
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
            return api_response([], 'کاربری با این شماره یا ایمیل پیدا نشد. ابتدا ثبت‌نام کنید.');
        }

        if ($data['grant_type'] === 'otp_grant') {
            if (!$user->sms_sent_date || Carbon::parse($user->sms_sent_date)->diffInMinutes(now()) > 2) {
                return api_response([], 'کد تایید منقضی شده است. لطفاً دوباره درخواست ارسال کد بدهید.');
            }

            if ($user->sms_sent_code !== $data['otp']) {
                return api_response([], 'کد تایید اشتباه است.');
            }
        }

        if ($data['grant_type'] === 'password') {
            if (!Hash::check($data['password'], $user->password)) {
                return api_response([], 'رمز عبور اشتباه است.');
            }
        }

        if (!$user->buyer) {
            Buyer::create([
                'user_id'    => $user->id,
                'first_name' => $user->first_name ?? '',
                'last_name'  => $user->last_name ?? '',
                'mobile'     => $user->mobile ?? null,
                'email'      => $user->email ?? null,
            ]);
        }

        Auth::login($user);
        $accessToken = $user->createToken('authToken')->plainTextToken;

        $userDataWithToken = $user->toArray();
        $userDataWithToken['token'] = [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
        ];

        return api_response($userDataWithToken);
    }
    public function otpRegister(Request $request)
    {
        $data = $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'required|string|max:255',
            'username'       => 'required|string|max:255|unique:users,username',
            'user'           => 'required', // mobile or email
            'otp'            => 'required|string',
            'referral_code'  => 'nullable|string',
        ]);

        $tempUser = TempUser::where('mobile', $data['user'])
            ->orWhere('email', $data['user'])
            ->first();

        if (!$tempUser) {
            return api_response([], 'کاربر  پیدا نشد.');
        }

        if (!$tempUser->sms_sent_date || Carbon::parse($tempUser->sms_sent_date)->diffInMinutes(now()) > 2) {
            return api_response([], 'کد تایید منقضی شده است. لطفاً دوباره درخواست ارسال کد بدهید.');
        }

        if ($tempUser->sms_sent_code !== $data['otp']) {
            return api_response([], 'کد تایید اشتباه است.');
        }

        DB::beginTransaction();

        $newUser = User::create([
            'first_name'     => $data['first_name'],
            'last_name'      => $data['last_name'],
            'username'       => $data['username'],
            'mobile'         => $tempUser->mobile,
            'email'          => $tempUser->email,
            'sms_sent_code'  => $tempUser->sms_sent_code,
            'password'       => bcrypt($tempUser->sms_sent_code), // set temporary password
        ]);

        $newUser->assignRole(role_value::buyer);
        event(new Registered($newUser));

        Auth::login($newUser);

        Buyer::create([
            'user_id'    => $newUser->id,
            'first_name' => $newUser->first_name,
            'last_name'  => $newUser->last_name,
            'mobile'     => $newUser->mobile,
            'email'      => $newUser->email,
            'referral_code'  => $data['referral_code'] ?? null,
        ]);

        $accessToken = $newUser->createToken('authToken')->plainTextToken;

        $userDataWithToken = $newUser->toArray();
        $userDataWithToken['token'] = [
            'token_type' => 'Bearer',
            'access_token' => $accessToken,
        ];
        $userDataWithToken['is_admin'] = $newUser->hasRole('adminController');

        DB::commit();

        return api_response($userDataWithToken);
    }

}
