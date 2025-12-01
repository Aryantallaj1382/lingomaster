<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class LoginController extends Controller
{

    public function apiLogin(LoginRequest $request)
    {
        $key = 'login:' . Str::lower($request->input('user'));

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return api_response([], "تعداد تلاش‌های ناموفق بیشتر از حد مجاز است. لطفاً بعد از $seconds ثانیه دوباره تلاش کنید.", 200);
        }


        if ($request->filled('password')) {
            $user = User::where('email', $request->input('user'))
                ->orWhere('mobile', $request->input('user'))
                ->first();

            if (!$user) {
                RateLimiter::hit($key);
                return api_response([], 'کاربری با این مشخصات یافت نشد', 401);
            } elseif (blank($user->password)) {
                RateLimiter::hit($key);
                return api_response([], 'رمز عبور تعیین نشده است', 401);
            } elseif (!Hash::check($request->input('password'), $user->password)) {
                RateLimiter::hit($key);
                return api_response([], 'رمز عبور اشتباه است', 401);
            } else {
                RateLimiter::clear($key);
                auth()->login($user);
            }

        } else {
            $user = User::where(function (Builder $query) use ($request) {
                $query->where('email', '=', $request->input('user'))
                    ->orWhere('mobile', '=', $request->input('user'));
            })
                ->where('sms_sent_code', '=', $request->input('otp'))
                ->first();

            if (!$user) {
                RateLimiter::hit($key);
                return api_response([], 'کاربری با این مشخصات یافت نشد', 401);
            } elseif ($user->sms_sent_date && $user->sms_sent_date->diffInSeconds(now()) > 180) {
                RateLimiter::hit($key);
                return api_response([], 'کد تایید منقضی شده است', 401);
            } elseif ($user->sms_sent_tries > 3) {
                $user->increment('sms_sent_tries');
                RateLimiter::hit($key);
                return api_response([], 'تعداد تلاش‌های ناموفق بیشتر از حد مجاز است', 401);
            } else {
                RateLimiter::clear($key);
                if (blank($user->mobile_verified_at)) {
                    $user->update(['mobile_verified_at' => now()]);
                }

                auth()->login($user);
            }
        }

        $user = auth()->user();
        $user->tokens()->delete();

        $expiresAt = now()->addHours(72);
        $remainingSeconds = $expiresAt->diffInSeconds(now(), false);
        if ($remainingSeconds < 0) {
            $remainingSeconds = 0;
        }
        $remainingDays = ceil($remainingSeconds / (60 * 60 * 24));

        $accessToken = $user->createToken('authToken')->plainTextToken;
        $token = PersonalAccessToken::findToken($accessToken);
        $token->expires_at = Carbon::now()->addDays(3);
        $token->save();
        if (!$user->student) {
            return api_response([], 'شما اجازه ورود به این سیستم را ندارید', 403);
        }
//        if ($user->roles->contains('name', 'student')) {/
            return api_response([
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'id' => $user->id,
                'student_id' => $user->student->id,
                'token' => [
                    'access_token' => $accessToken,
                    'token_type' => 'Bearer',
                    'expires_at' => 3,
                ]
            ], 'با موفقیت وارد شدید');
//        } else {
//            return api_response([], 'شما اجازه ورود به این سیستم را ندارید', 403);
//        }
    }


    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return api_response([],'با موفقیت از حساب خود خارج شدید ');
    }
}
