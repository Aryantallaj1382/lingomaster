<?php

namespace App\Http\Controllers\Api\auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUser;
use App\Models\contacts\role_value;
use App\Models\Financial\Wallet;
use App\Models\Professors\Attachment;
use App\Models\Professors\Professor;
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
use Illuminate\Support\Facades\Storage;

class RegisteredUserController extends Controller
{


    public function store(RegisterUser $request)
    {
        $userData = json_decode($request->getContent(), true);

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
            Log::info('Password before hashing: ' . $request->password);

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
            } while (Professor::where('professor_code', $professorCode)->exists());

            $professor = Professor::create([
                'user_id' => $newUser->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'mobile' => $mobile,
                'email' => $request->email,
                'date_of_birth' => $request->date_of_birth,
                'identification_code' => $request->Identification_code,
                'professor_code' => $professorCode, // 👈 اضافه شد

            ]);

            $fileCategories = [
                Attachment::CATEGORY_FOREIGN_LANGUAGE_CERTIFICATE,
                Attachment::CATEGORY_LOCAL_LANGUAGE_CERTIFICATE,
                Attachment::CATEGORY_UNIVERSITY_DEGREE,
                Attachment::CATEGORY_STUDENT_CARD,
            ];

            $isStudent = 0;

            foreach ($fileCategories as $category) {
                if ($request->hasFile($category)) {
                    $file = $request->file($category);

                    $filename = $category . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                    $path = 'uploads/' . $filename;
                    Storage::disk('public')->putFileAs('uploads', $file, $filename);
                    $filePath = $path;

                    $professor->attachments()->create([
                        'file_name' => $filename,
                        'file_path' => $filePath,
                        'category' => $category,
                    ]);

                    if ($category === Attachment::CATEGORY_STUDENT_CARD) {
                        $isStudent = 1;
                    }
                }
            }

            $professor->update([
                'is_student' => $isStudent,
            ]);

            $newUser->assignRole(\App\Models\contacts\role_value::professor);

            event(new Registered($newUser));

            Auth::login($newUser);

            $accessToken = $newUser->createToken('authToken')->plainTextToken;

            $userDataWithToken = $newUser->toArray();
            $userDataWithToken['token'] = [
                'token_type' => 'Bearer',
                'access_token' => $accessToken,
            ];
            $userDataWithToken['is_admin'] = $newUser->hasRole('adminController');
            $userDataWithToken['professor_id'] =$professor->id;

            DB::commit();
            $smsService = new SmsServiceServices();

            $n = $professor->mobile;
            $d =   $smsService->sendPatternSms(
                '9i8i8yxq2oyozhv',
                $n,
                [
                    'name' =>$professor->full_name(),
                    'link' => 'https://professor.lingomasters.ir/auth/login',

                ]
            );
            return api_response($userDataWithToken, 'عملیات موفقیت‌آمیز بود', 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error($th);

            return api_response([], 'خطایی در ثبت نام رخ داد', 500);
        }
    }


}
