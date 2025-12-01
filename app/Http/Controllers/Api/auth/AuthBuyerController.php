<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Models\Buyer\Buyer;
use App\Models\contacts\role_value;
use App\Models\TempUser;
use App\Models\User;
use App\Services\SmsServiceServices;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthBuyerController extends Controller
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
        return api_response($userDataWithToken , 'خوش آمدید');
    }
    public function otpRegister(Request $request)
    {
        $data = $request->validate([
            'first_name'         => 'required|string|max:255',
            'last_name'          => 'required|string|max:255',
            'username'           => 'nullable|string|max:255|unique:users,username',
            'email'              => 'nullable|email|max:255|unique:users,email',
            'mobile'             => 'required|string|unique:users,mobile',
            'otp'                => 'required|string',
            'password'           => 'required|string|min:4',
            'confirm_password'   => 'required|string|same:password',
            'birthday'           => 'nullable|date',
            'Identification_code'=> 'nullable|string|max:255',
            'referral_code'      => 'nullable|string|max:255',
        ]);

        $tempUser = TempUser::where('mobile', $data['mobile'])
            ->orWhere('email', $data['email'])
            ->first();

        if (!$tempUser) {
            return api_response([], 'کاربر پیدا نشد.');
        }

        if (!$tempUser->sms_sent_date || Carbon::parse($tempUser->sms_sent_date)->diffInMinutes(now()) > 2) {
            return api_response([], 'کد تایید منقضی شده است. لطفاً دوباره درخواست ارسال کد بدهید.');
        }

        if ($tempUser->sms_sent_code !== $data['otp']) {
            return api_response([], 'کد تایید اشتباه است.');
        }

        DB::beginTransaction();

        $newUser = User::create([
            'first_name'        => $data['first_name'],
            'last_name'         => $data['last_name'],
            'username'          => $data['username'] ?? ('user_' . rand(100000, 999999)),
            'mobile'            => $data['mobile'],
            'email'             => $data['email'] ?? null,
            'password'          => bcrypt($data['password']),
            'sms_sent_code'     => $tempUser->sms_sent_code,
        ]);

        $newUser->assignRole(role_value::buyer);
        event(new Registered($newUser));

        Auth::login($newUser);

        Buyer::create([
            'user_id'            => $newUser->id,
            'first_name'         => $newUser->first_name,
            'last_name'          => $newUser->last_name,
            'mobile'             => $newUser->mobile,
            'email'              => $newUser->email,
            'referral_code'      => $data['referral_code'] ?? null,
            'date_of_birth'           => isset($data['birthday']) ? Carbon::parse($data['birthday'])->format('Y-m-d') : null,
            'national_code'=> $data['Identification_code'] ?? null,
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
            'whf3kdp23lxzpb2',
            $n,
            [
                'name' =>$newUser->getFullName(),

            ]
        );

        DB::commit();

        return api_response($userDataWithToken);
    }

}
