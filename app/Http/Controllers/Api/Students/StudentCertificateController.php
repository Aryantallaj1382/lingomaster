<?php

namespace App\Http\Controllers\Api\Students;

use App\Helpers\zarinpal;
use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\contacts\Certificate;
use App\Models\Financial\Payment;
use App\Models\Financial\Transaction;
use App\Models\Professors\GroupClass;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Webinar;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\WorkShop;
use App\Models\Professors\WorkShopReservation;
use App\Models\StudentCertificate;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class StudentCertificateController extends Controller
{
    public function index(Request $request)
    {
        $student = auth()->user();
        $type = $request->input('type');
        if ($type === 'webinar') {

            $certificates = StudentCertificate::where('payment_status','paid')->where('certificatable_type', WebinarReservation::class)->where('student_id', $student->id)->with('student', 'city', 'certificatable')->latest()->paginate(20);
            $certificates->getCollection()->transform(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'title' => $certificate->certificatable?->webinar?->subject?->title,
                    'status' =>$certificate->status,
                    'tracking_code' => $certificate->tracking_code,
                    'delivery_type' => $certificate->delivery_type,
                    'file' => $certificate->file_path ?  api::api.$certificate->file_path :null,
                    'model'=>'وبینار',

                ];
            });
            return api_response($certificates);

        }
        if ($type ==='work_shop') {
            $certificates = StudentCertificate::where('certificatable_type', WorkShopReservation::class)->where('student_id', $student->id)->with('student', 'city', 'certificatable')->latest()->paginate(20);
            $certificates->getCollection()->transform(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'title' => $certificate->certificatable?->WorkShop?->subject?->title,
                    'status' =>$certificate->status,
                    'tracking_code' => $certificate->tracking_code,
                    'delivery_type' => $certificate->delivery_type,
                    'file' => $certificate->file_path ?  api::api.$certificate->file_path :null,
                    'model'=>'ورکشاپ',
                ];
            });
            return api_response($certificates);

        }
        if ($type === 'private') {
            $certificates = StudentCertificate::where('certificatable_type', PrivetClassReservation::class)->where('student_id', $student->id)->with('student', 'city', 'certificatable')->latest()->paginate(20);
            $certificates->getCollection()->transform(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'title' => $certificate->certificatable?->subject?->title,
                    'status' =>$certificate->status,
                    'tracking_code' => $certificate->tracking_code,
                    'delivery_type' => $certificate->delivery_type,
                    'file' => $certificate->file_path ?  api::api.$certificate->file_path :null,
                    'model'=>'کلاس خصوصی',
                ];

            });
            return api_response($certificates);

        }
        if ($type === 'group') {
            $certificates = StudentCertificate::where('certificatable_type', GroupClassReservation::class)->where('student_id', $student->id)->with('student', 'city', 'certificatable')->latest()->paginate(20);
            $certificates->getCollection()->transform(function ($certificate) {
                return [
                    'id' => $certificate->id,
                    'title' => $certificate->certificatable->groupClass->subject->title,
                    'status' =>$certificate->status,
                    'tracking_code' => $certificate->tracking_code,
                    'delivery_type' => $certificate->delivery_type,
                    'file' => $certificate->file_path ?  api::api.$certificate->file_path :null,
                    'model'=>'کلاس گروهی',
                ];
            });
            return api_response($certificates);

        }

        return api_response();
    }
    public function class(Request $request)
    {
        $user = auth()->user()->student;
       $type =$request->input('type');
       if ($type === 'webinar') {
           $webinar = WebinarReservation::where('student_id', $user->id)
               ->where('payment_status', 'paid')
               ->where('status', 'confirmed')->get();
           $class = $webinar->map(function ($webinar) {
              return [
                  'id' => $webinar->id,
                  'title' => $webinar->webinar->subject->title,
              ];
           });
           return api_response($class);
       }
        if ($type === 'workshop') {
            $webinar = WorkShopReservation::where('student_id', $user->id)
                ->where('payment_status', 'paid')
                ->where('status', 'confirmed')->get();
            $class = $webinar->map(function ($webinar) {
                return [
                    'id' => $webinar->id,
                    'title' => $webinar->WorkShop->subject->title,
                ];
            });
            return api_response($class);
        }
        if ($type === 'group') {
            $webinar = GroupClassReservation::where('student_id', $user->id)
                ->where('payment_status', 'paid')
                ->where('status', 'confirmed')->get();
            $class = $webinar->map(function ($webinar) {
                return [
                    'id' => $webinar->id,
                    'title' => $webinar->groupClass->subject->title,
                ];
            });
            return api_response($class);
        }
        if ($type === 'private') {
            $webinar = PrivetClassReservation::where('student_id', $user->id)
                ->where('payment_status', 'paid')
                ->where('status', 'confirmed')->get();
            $class = $webinar->map(function ($webinar) {
                return [
                    'id' => $webinar->id,
                    'title' => $webinar->subject->title,
                ];
            });
            return api_response($class);
        }

    }

    public function store(Request $request)
    {
        $request->validate([
            'delivery_type' => 'required',
            'email' => 'nullable',
            'mobile' => 'nullable',
            'name_fa' => 'required',
            'name_en' => 'required',
            'city' => 'nullable',
            'region' => 'nullable',
            'address' => 'nullable',
            'postal_code' => 'nullable',
            'language' => 'required',
            'class' => 'required',
            'type' => 'required',
        ]);
        if ($request->type === 'webinar') {
            $type = WebinarReservation::class;
        }
        elseif ($request->type === 'workshop') {
            $type = WorkShopReservation::class;

        }
        elseif ($request->type === 'group') {
            $type = GroupClassReservation::class;

        }
        elseif ($request->type === 'private') {
            $type = PrivetClassReservation::class;

        }

        $Certificate = StudentCertificate::create([
            'delivery_type' => $request->delivery_type,
            'email' => $request->email ?? null,
            'mobile' => $request->mobile ?? null,
            'name_fa' => $request->name_fa,
            'name_en' => $request->name_en,
            'city' => $request->city ?? null,
            'region' => $request->region ?? null ,
            'address' => $request->address ?? null,
            'postal_code' => $request->postal_code ?? null,
            'language_id' => $request->language,
            'certificatable_id'=> $request->class,
            'certificatable_type' => $type,
            'student_id' => auth()->user()->id,
            'status' => 'pending',

        ]);
        return api_response($Certificate->id,'');


    }

    public function info1(Request $request, $id)
    {
        $Certificate = StudentCertificate::find($id);
        if ($Certificate->delivery_type == 'physical')
            $price = SystemSetting::where('name' , Certificate::physical)->value('value');
        if ($Certificate->delivery_type == 'online')
            $price = SystemSetting::where('name' , Certificate::online)->value('value');

        if ($Certificate->certificatable_type == WebinarReservation::class) {
            return api_response([
                'id' => $Certificate->id,
                'name_fa' => $Certificate->name_fa,
                'name_en' =>$Certificate->name_en,
                'city' => $Certificate->city,
                'region' => $Certificate->region,
                'postal_code' => $Certificate->postal_code,
                'address' => $Certificate->address,
                'mobile' => $Certificate->mobile,
                'email' => $Certificate->email,
                'model' => 'گواهینامه کلاس وبینار',
                'teaching_type' => 'آنلاین',
                'delivery_type' => $Certificate->delivery_type,
                'professor'=> $Certificate->certificatable->webinar->professor->full_name(),
                'professor_id'=> $Certificate->certificatable->webinar->professor->id,
                'profile'=> $Certificate->certificatable->webinar->professor->profile ? api::api.$Certificate->certificatable->webinar->professor->profile : null,
                'language'=> $Certificate->certificatable?->webinar?->language?->title,
                'flag'=> $Certificate->certificatable?->webinar?->language?->icon,
                'ageGroup'=> $Certificate->certificatable->webinar->ageGroup->title,
                'level'=> $Certificate->certificatable->webinar->level->title,
                'subject'=> $Certificate->certificatable->webinar->subject->title,
                'session_count'=> 1,
                'price'=> (int)$price,

            ]);
        }
        if ($Certificate->certificatable_type == WorkShopReservation::class) {
            return api_response([
                'id' => $Certificate->id,
                'name_fa' => $Certificate->name_fa,
                'name_en' =>$Certificate->name_en,
                'city' => $Certificate->city,
                'region' => $Certificate->region,
                'postal_code' => $Certificate->postal_code,
                'address' => $Certificate->address,
                'model' => 'گواهینامه کلاس ورکشاپ',
                'profile'=> $Certificate->certificatable->webinar->professor->profile ? api::api.$Certificate->certificatable->webinar->professor->profile : null,

                'teaching_type' => 'حضوری',
                'delivery_type' => $Certificate->delivery_type,
                'professor'=> $Certificate->certificatable->WorkShop->professor->full_name(),
                'professor_id'=> $Certificate->certificatable->WorkShop->professor->id,
                'language'=> $Certificate->certificatable->WorkShop->language->title,
                'flag'=> $Certificate->certificatable->WorkShop->language->icon,
                'ageGroup'=> $Certificate->certificatable->WorkShop->ageGroup->title,
                'level'=> $Certificate->certificatable->WorkShop->level->title,
                'subject'=> $Certificate->certificatable->WorkShop->subject->title,
                'session_count'=> 1,
                'price'=> (int)$price,

            ]);
        }
        if ($Certificate->certificatable_type == GroupClassReservation::class) {
            return api_response([
                'id' => $Certificate->id,
                'name_fa' => $Certificate->name_fa,
                'name_en' =>$Certificate->name_en,
                'city' => $Certificate->city,
                'region' => $Certificate->region,
                'postal_code' => $Certificate->postal_code,
                'address' => $Certificate->address,
                'delivery_type' => $Certificate->delivery_type,
                'professor'=> $Certificate->certificatable->groupClass->professor->full_name(),
                'professor_id'=> $Certificate->certificatable->groupClass->professor->id,
                'teaching_type'=> $Certificate->certificatable->groupClass->teachingType->title,
                'language'=> $Certificate->certificatable->groupClass->language->title,
                'flag'=> $Certificate->certificatable->groupClass->language->icon,
                'ageGroup'=> $Certificate->certificatable->groupClass->ageGroup->title,
                'level'=> $Certificate->certificatable->groupClass->level->title,
                'subject'=> $Certificate->certificatable->groupClass->subject->title,
                'session_count'=> $Certificate->certificatable->groupClass->sessions_count,
                'price'=> (int)$price,
                'model' => 'گواهینامه کلاس گروهی',
                'profile'=> $Certificate->certificatable->webinar->professor->profile ? api::api.$Certificate->certificatable->webinar->professor->profile : null,

            ]);
        }
        if ($Certificate->certificatable_type == PrivetClassReservation::class) {
            return api_response([
                'id' => $Certificate->id,
                'name_fa' => $Certificate->name_fa,
                'name_en' =>$Certificate->name_en,
                'city' => $Certificate->city,
                'region' => $Certificate->region,
                'postal_code' => $Certificate->postal_code,
                'address' => $Certificate->address,
                'teaching_type'=> $Certificate->certificatable->teachingType->title,
                'delivery_type' => $Certificate->delivery_type,
                'professor'=> $Certificate->certificatable->professor->full_name(),
                'professor_id'=> $Certificate->certificatable->professor->id,
                'language'=> $Certificate->certificatable->language->title,
                'flag'=> $Certificate->certificatable->language->icon,
                'ageGroup'=> $Certificate->certificatable->ageGroup->title,
                'level'=> $Certificate->certificatable->languageLevel->title,
                'subject'=> $Certificate->certificatable->subject->title,
                'session_count'=>  $Certificate->certificatable->teacherTimeSlots->count(),
                'price'=> (int)$price,
                'model' => 'گواهینامه کلاس خصوصی',
                'profile'=> $Certificate->certificatable->webinar->professor->profile ? api::api.$Certificate->certificatable->webinar->professor->profile : null,

            ]);
        }


    }








    public function pay_group_reservation($reservationId)
    {
        $studentId = auth()->user()->student->id;

        $reservation =  StudentCertificate::find($reservationId);
        if ($reservation->delivery_type == 'physical')
            $amount = SystemSetting::where('name' , Certificate::physical)->value('value');
        if ($reservation->delivery_type == 'online')
            $amount = SystemSetting::where('name' , Certificate::online)->value('value');


        $description = "پرداخت رزرو کلاس گروهی " ;

        $paymentService = new zarinpal();
        $callback ='https://api.lingomasters.ir/zarin_callback_certificate';
        return $paymentService->pay($reservation, $amount, $description , $callback);
    }

    public function zarin_callback(Request $request)
    {
        if (!$request->filled('Authority') || !$request->filled('Status'))
            return api_response([], 'خطا در پاسخ درگاه پرداخت', 403);

        $order_payment = Payment::where('authority', '=', $request->Authority)->first();


        if (blank($order_payment))
            return api_response([], 'اطلاعات پرداخت حذف شده است', 403);


        $amount =  $order_payment->amount;
        $authority = $order_payment->authority;
        $response = zarinpal()
            ->merchantId(config('zarinpal.merchant_id'))
            ->amount($amount)
            ->verification()
            ->authority($authority)
            ->send();
        $redirect_url = 'https://lingomasters.ir/profile/cetificate' ;

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $payable = $order_payment->payable;
            if ($payable instanceof StudentCertificate) {
                $payable->update([
                    'payment_status' => 'unpaid',
                ]);


            }

            return redirect($redirect_url);
        }
        $ref =  $response->referenceId();


        $payable = $order_payment->payable;
        if ($payable instanceof StudentCertificate) {
            $payable->update([
                'payment_status' => 'paid',
            ]);

        }

        $order_payment->update([
            'ref_id' =>$ref,
            'status' => 'paid',
        ]);



        return redirect($redirect_url);
    }


    public function pay_private_reservation_Wallet($reservationId)
    {
        $wallet = auth()->user()->wallet;
        $reservation = StudentCertificate::find($reservationId);

        if (!$reservation){
            return api_response(null, '<UNK> <UNK> <UNK> <UNK>', 404);
        }
        $user_id = auth()->user()->id;

        $now = now()->format('His'); // ساعت به صورت 143251
        $rawString = $reservationId . '_' . $user_id . '_' . $now;

        $authority = crc32($rawString); // خروجی عدد صحیح

        if ($reservation->delivery_type == 'physical')
            $amount = SystemSetting::where('name' , Certificate::physical)->value('value');
        if ($reservation->delivery_type == 'online')
            $amount = SystemSetting::where('name' , Certificate::online)->value('value');
        $description = "پرداخت درخواست گواهینامه " ;

        $pa = $reservation->payment()->create([
            'user_id' => auth()->id(),
            'gateway_id' => 2,
            'amount' => $amount,
            'authority' =>$authority,
            'status' => 'pending',
            'description' => $description,
        ]);
        $ta =  Transaction::create([
            'type' => 'payment',
            'amount' => $amount,
            'description' => $description,
            'wallet_id' => $wallet->id,
            'status' => 'Pending',
        ]);
        if ($wallet->balance > $amount) {
            $wallet->balance -= $amount;
            $wallet->save();
            $ta->update([
                'status' => 'approved',
            ]);
            $pa->update([
                'status' => 'paid',
            ]);
            $reservation->update([
                'payment_status' =>'paid',
            ]);
            return api_response([],'پرداخت انجام شد و درخواست شما بزودی برسی خواهد شد', 200);

        }
        else{
            $ta->update([
                'status' => 'rejected',
            ]);
            $pa->update([
                'status' => 'failed',
            ]);

            return api_response([],'موجودی شما برای پرداخت کافی نمیباشد', 400);
        }


    }


}
