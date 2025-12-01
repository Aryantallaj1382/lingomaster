<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Models\TempUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginSellerController extends Controller
{
    public function check(Request $request)
    {
        $request->validate([
            'user'=> 'required',
        ]);
        $user = User::where('email', $request->user)->orWhere('mobile' , $request->user)->first();
        if ($user) {
            return api_response([
                'is_exist' => true
            ]);
        }
        return api_response([
            'is_exist' => false
        ]);
    }



    public function otpLogin(Request $request)
    {
        $data = $request->validate([
            'grant_type' => 'required|in:otp_grant,password',
            'mobile' => 'required|string',
            'email' => 'required|email',
            'otp' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        $user = User::where('mobile', $data['mobile'])->orWhere('email',$data['email'])->first();

        if ($user) {
            if (
                ($data['grant_type'] === 'otp_grant' && $user->sms_sent_code === $data['otp']) ||
                ($data['grant_type'] === 'password' && Hash::check($data['password'], $user->password))
            ) {
                $token = $user->createToken('authToken')->plainTextToken;
                auth()->login($user);
                return response()->json([
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_at' => 3,

                ]);
            }

            return api_response([], 'کد یا رمز عبور اشتباه است.');
        }

        if ($data['grant_type'] === 'otp_grant') {
            $tempUser = TempUser::where('mobile', $data['mobile'])->orWhere('email',$data['email'])->first();

            if ($tempUser && $tempUser->sms_sent_code === $data['otp']) {
                $newUser = User::create([
                    'mobile' => $tempUser->mobile,
                    'sms_sent_code' => $tempUser->sms_sent_code,
                    'password' => bcrypt($tempUser->sms_sent_code), // در صورت نیاز
                ]);

                $token = $newUser->createToken('authToken')->plainTextToken;
                auth()->login($user);

                return api_response([
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'expires_at' => 3,
                ]);
            }

            return api_response([],'کاربر موقت پیدا نشد یا کد اشتباه است.');
        }

        return api_response([], 'کاربری با این شماره وجود ندارد.');
    }

}
