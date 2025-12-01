<?php

namespace App\Console\Commands;

use App\Models\Professors\Professor;
use App\Models\Student\Student;
use App\Services\SmsServiceServices;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBirthdayProfessorSms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-birthday-professor-sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->format('m-d');
        $students = Professor::whereRaw("DATE_FORMAT(birth, '%m-%d') = ?", [$today])
            ->get();

        $smsService = new SmsServiceServices();

        foreach ($students as $student) {

            $mobile = $student->mobile;

            $smsService->sendPatternSms(
                'fyilavyaiyuy64j',
                $mobile,
                [
                    'name' => 'استاد گرامی '.$student->full_name(),
                ]
            );
        }
    }
}
