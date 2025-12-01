<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswrodRequest;
use Illuminate\Support\Facades\Hash;

class  PasswordController extends Controller
{
    public function changePassword(PasswrodRequest $request)
    {
        $user = $request->user();

        if (!Hash::check($request->old_password, $user->password)) {
            return api_response([], 'رمز عبور قدیمی نادرست است', 400);
        }

        if (Hash::check($request->password, $user->password)) {
            return api_response([], 'رمز عبور جدید نمی‌تواند با رمز عبور قبلی یکسان باشد', 400);
        }

        $user->password = Hash::make($request->password);if ($request->filled('otp')) {
            $user->fill([
                'sms_sent_tries' => 0,
                'sms_sent_date' => null,
            ]);
        }

        $user->update();

        return api_response([], 'رمز عبور با موفقیت تغییر کرد', 200);
    }


}
