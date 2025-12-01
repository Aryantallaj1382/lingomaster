<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;
use App\Services\SmsServiceServices;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use IPPanel\Client;

class sendMessageController extends Controller
{


    public function checkBirthdaysAndSendSms()
    {
        $today = Carbon::now()->format('m-d');

        $students = Student::whereRaw("DATE_FORMAT(date_of_birth, '%m-%d') = ?", [$today])
            ->get();

        $smsService = new SmsServiceServices();

        foreach ($students as $student) {

            $mobile = $student->mobile;

            $smsService->sendPatternSms(
                'fyilavyaiyuy64j',
                $mobile,
                [
                    'name' => $student->full_name(),

                ]
            );
        }
    }





}
