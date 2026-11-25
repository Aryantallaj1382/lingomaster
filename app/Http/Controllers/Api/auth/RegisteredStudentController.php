<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUser;
use App\Models\contacts\role_value;
use App\Models\Financial\Wallet;
use App\Models\Professors\Attachment;
use App\Models\Professors\Professor;
use App\Models\Student\Student;
use App\Models\TempUser;
use App\Models\User;
use App\Services\SmsServiceServices;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class RegisteredStudentController extends Controller
{


    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'nullable|string|max:40',
            'last_name' => 'nullable|string|max:40',
            'identification_code' => 'nullable',
            'email' => 'nullable|string|email|max:255|unique:' . User::class . ',email',
            'mobile' => 'nullable|digits:11|ir_mobile:zero|unique:' . User::class . ',mobile',
            'password' => 'required',
            'date_of_birth' => 'required|date',
        ]);

        if (!isset($request->mobile, $request->otp)) {
            return api_response([], 'اطلاعات ارسال‌شده ناقص است', 400);
        }

        $mobile =$request->mobile;
        $otp = $request->otp;

        $tempUser = TempUser::where('mobile', $mobile)
            ->where('sms_sent_code', $otp)
            ->first();

        if (!$tempUser) {
            return api_response([], 'کد یکبار مصرف نادرست است یا منقضی شده است', 401);
        }

        try {
            DB::beginTransaction();

            $newUser = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'mobile' => $mobile,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'mobile_verified_at' => now(),
            ]);
            Wallet::create([
                'user_id' => $newUser->id,
                'balance' => 0,
            ]);
            do {
                $professorCode = rand(10000000, 99999999); // کد ۸ رقمی
            } while (Student::where('student_code', $professorCode)->exists());

            $date = Carbon::parse($request->date_of_birth)->format('Y-m-d');

            $student = Student::create([
                'user_id' => $newUser->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'mobile' => $mobile,
                'email' => $request->email,
                'date_of_birth' =>$date,
                'identification_code' => $request->Identification_code,
                'student_code' => $professorCode, // 👈 اضافه شد

            ]);





            $newUser->assignRole(role_value::student);

            event(new Registered($newUser));

            Auth::login($newUser);

            $accessToken = $newUser->createToken('authToken')->plainTextToken;

            $userDataWithToken = $newUser->toArray();
            $userDataWithToken['token'] = [
                'token_type' => 'Bearer',
                'access_token' => $accessToken,
            ];
//            $userDataWithToken['is_admin'] = $newUser->hasRole('adminController');
            $userDataWithToken['student_id'] =$student->id;

            DB::commit();
            $smsService = new SmsServiceServices();

            $n = $student->mobile;
            $d =   $smsService->sendPatternSms(
                's7ebgbijdo5uwz2',
                $n,
                [
                    'name' =>$student->full_name(),
                    'link' => 'https://lingomasters.ir/auth/login',

                ]
            );
            return api_response($userDataWithToken, 'عملیات موفقیت‌آمیز بود', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);

            return api_response($th, 'خطایی در ثبت نام رخ داد', 500);
        }
    }


}
