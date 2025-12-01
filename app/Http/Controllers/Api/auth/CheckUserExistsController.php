<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class CheckUserExistsController extends Controller
{
    public function checkUserExists(Request $request)
    {
        $request->validate([
            'user' => 'required|string',
        ]);

        $user = User::where('email', $request->input('user'))
            ->orWhere('mobile', $request->input('user'))
            ->first();
        if ($user) {
            return api_response([
                'exists' => true,
                'message' => 'کاربر با این ایمیل یا شماره موبایل قبلاً ثبت‌نام کرده است.'
            ]);
        } else {
            return api_response([
                'exists' => false,
                'message' => 'کاربری با این ایمیل یا شماره موبایل وجود ندارد.'
            ]);
        }
    }
}
