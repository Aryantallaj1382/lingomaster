<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Professors\GroupClass;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Webinar;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\WorkShop;
use App\Models\Professors\WorkShopReservation;
use App\Models\ReportHomeWork;
use App\Models\ReportRegistration;
use App\Models\shop\Story;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class ProfessorDashboardController extends Controller
{
    public function index()
    {
        $professor = auth()->user()->professor->id;
        $languages = Languages::get();



        $reports = ReportRegistration::whereRelation('classReservation', 'professor_id', $professor)
            ->whereRelation('classReservation', 'payment_status', 'paid')
            ->get()
            ->filter(function ($report) {
                return ReportHomeWork::where('report_registration_id', $report->id)->exists();
            });

        $data = $reports->map(function ($report, $index) {
            $reservation = optional($report->classReservation);
            $home = ReportHomeWork::where('report_registration_id', $report->id)->first();

            return [
                'number'     => $index + 1,
                'title'      => optional($reservation->subject)->title,
                'student'  => optional($reservation->student)->full_name(),
                'is_reading' => $home->is_reading ?? null,
                'homework_file'   => api::api.$home->answer ?? null, // اگر از قبل تعریف نشده، این خط ارور خواهد داد
                'id'  => $home->id,
                'status' => $home->status
            ];
        });




        $privet_count = PrivetClassReservation::where('professor_id', $professor)
            ->where(function ($q) {
                $q->where('class_type','multi-session')
                    ->orWhere('class_type','affordable');
            })
            ->count();
        $privet_trial = PrivetClassReservation::where('professor_id', $professor)->where('class_type','trial')->orWhere('class_type',)->count();
        $privet_level_assessment = PrivetClassReservation::where('professor_id', $professor)->where('class_type','level_assessment')->orWhere('class_type',)->count();
        $group_count = GroupClassReservation::whereRelation('groupClass','professors_id','=',$professor)->count();
        $webinar_count = WebinarReservation::whereRelation('webinar','professors_id','=',$professor)->count();
        $workshop = WorkShopReservation::whereRelation('WorkShop','professors_id','=',$professor)->count();
        $lang =$languages->map(function ($language) {
           return [
               'id' => $language->id,
               'language' => $language->title,
               'flag' => $language->flag,
           ];
       });

        $near_group1 = GroupClass::where('professors_id', $professor)
            ->where('admin_status', 'approved')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->first();

        $near_group = $near_group1 ? [
            'id'         => $near_group1->id,
            'date'       => $near_group1->start_date,
            'time' =>       $near_group1->schedules->first()->start_time,
            'type'   => 'حضوری',
            'class'   => 'ورکشاپ',
            'title'   => $near_group1->subject->title,
            'language'   => $near_group1->language->title,
        ] : null;

        $now = now()->format('Y-m-d H:i:s');

        $near_privet = PrivetClassReservation::where('professor_id', $professor)
            ->whereHas('teacherTimeSlots', function ($q) use ($now) {
                $q->whereRaw("STR_TO_DATE(CONCAT(date, ' ', time), '%Y-%m-%d %H:%i:%s') >= ?", [$now]);
            })
            ->with(['teacherTimeSlots' => function ($q) use ($now) {
                $q->whereRaw("STR_TO_DATE(CONCAT(date, ' ', time), '%Y-%m-%d %H:%i:%s') >= ?", [$now])
                    ->orderByRaw("STR_TO_DATE(CONCAT(date, ' ', time), '%Y-%m-%d %H:%i:%s')")
                    ->limit(1);
            }])
            ->get()
            ->sortBy(function ($reservation) {
                $slot = $reservation->teacherTimeSlots->first();
                return $slot ? strtotime($slot->date . ' ' . $slot->time) : PHP_INT_MAX;
            })
            ->first();

        $mapped = $near_privet ? [
            'reservation_id' => $near_privet->id,
            'student_id'     => $near_privet->student_id,
            'date'   => $near_privet->teacherTimeSlots->first() ? $near_privet->teacherTimeSlots->first()->date : null,
            'time'   => $near_privet->teacherTimeSlots->first() ? $near_privet->teacherTimeSlots->first()->time : null,
            'language'   => $near_privet ->language->title,

        ] : null;

        $near_webinar1 = Webinar::where('professors_id', $professor)
            ->where('admin_status', 'approved')
            ->where('date', '>=', now())
            ->orderBy('date')
            ->first();

        $near_webinar = $near_webinar1 ? [
            'id'         => $near_webinar1->id,
            'date'       => $near_webinar1->date,
            'time'       => $near_webinar1->hour,
            'headline'   => $near_webinar1->headline,
            'platform'   => $near_webinar1->platform,
            'type'   => 'آنلاین',
            'class'   => 'وبینار',
            'title'   => $near_webinar1->subject->title,
            'language'   => $near_webinar1->language->title,
        ] : null;


        $near_work_shop1 = WorkShop::where('professors_id', $professor)
            ->where('admin_status', 'approved')
            ->where('date', '>=', now())
            ->orderBy('date')
            ->first();


        $near_work_shop = $near_work_shop1 ? [
            'id'         => $near_work_shop1->id,
            'date'       => $near_work_shop1->date,
            'time'       => $near_work_shop1->hour,
            'headline'   => $near_work_shop1->headline,
            'type'   => 'حضوری',
            'class'   => 'ورکشاپ',
            'title'   => $near_work_shop1->subject->title,
            'language'   => $near_work_shop1->language->title,
        ] : null;

        $privet = PrivetClassReservation::where(function ($q) {
            $q->where('status', 'progress')
                ->orWhere('status', 'reserved');
        })
            ->where(function ($q) {
                $q->where('class_type', 'multi-session')
                    ->orWhere('class_type', 'affordable')
                    ->orWhere('class_type', 'trial');
            })
            ->where('professor_id', $professor)
            ->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'student_id' => $item->student->id,
                'professor_id' => $item->professor->id,
                'student_name' => $item->student->full_name(),
                'student_profile' => $item->student->profile,
                'teachingType' => $item->teachingType->title,
                'teachingTypeId' => $item->teachingType->id,
                'status' => $item->status,
                'date' => optional($item->teacherTimeSlots->where('date', '>', now())->sortBy('date')->first())->date,
                'time' => optional($item->teacherTimeSlots->where('date', '>', now())->sortBy('date')->first())->time,
                'class' => 'خصوصی',
            ];
        });


        $group = GroupClass::where('professors_id', $professor)->where('professor_status','progress')->orWhere('professor_status','reserved')->take(3)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'teachingType' => $item->teachingType->title,
                'time' => optional($item->schedules->where('date', '>', now())->sortBy('date')->first())->time,
                'date' => optional($item->schedules->where('date', '>', now())->sortBy('date')->first())->date,
                'status' => $item->professor_status,
                'teachingTypeId' => $item->teachingType->id,
                'class' => 'گروهی',
            ];
        });

        $webinar = Webinar::where('professors_id', $professor)->where('professor_status','progress')->orWhere('professor_status','reserved')->take(3)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'teachingType' =>'آنلاین',
                'time' => $item->hour,
                'date' => $item->date,
                'status' => $item->professor_status,
                'teachingTypeId' => '2',
                'class' => 'وبینار',
            ];
        });

        $workShop = WorkShop::where('professors_id', $professor)->where('professor_status','progress')->orWhere('professor_status','reserved')->take(3)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'teachingType' => 'حضوری',
                'time' => $item->hour,
                'date' => $item->date,
                'status' => $item->professor_status,
                'teachingTypeId' => '1',
                'class' => 'ورکشاپ',
            ];
        });
        $group = collect($group);
        $webinar = collect($webinar);
        $workShop = collect($workShop);
        $allClasses = $group->merge($webinar)->merge($workShop);



        $privet_level = PrivetClassReservation::where('professor_id', $professor)->where('status','progress')->orWhere('status','reserved')->where('class_type','multi-level_assessment')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'student_id' => $item->student->id,
                'student_name' => $item->student->full_name(),
                'student_profile' => $item->student->profile,
                'teachingType' => $item->teachingType->title,
                'teachingTypeId' => $item->teachingType->id,
                'status' => $item->status,
                'date' => optional($item->teacherTimeSlots->where('date', '>', now())->sortBy('date')->first())->date,
                'time' => optional($item->teacherTimeSlots->where('date', '>', now())->sortBy('date')->first())->time,
                'class' => 'خصوصی',
                'skill' => 'مکالمه'
            ];
        });

        $authUserId = auth()->id();

        $stories = Story::where('is_approved', true)->get()->map(function ($item) use ($authUserId) {
            return [
                'id' => $item->id,
                'cover' => $item->cover ? $item->cover : api::api . $item->user->professor->profile,
                'title' => optional($item->user->professor)->full_name() ?? 'نامشخص',
                'profile' => optional($item->professor)->profile ? api::api . optional($item->professor)->profile : null,
                'is_main' => $item->user_id === $authUserId,
            ];
        });
        return api_response([
            'private_count'=>$privet_count,
            'private_trial_count'=>$privet_trial,
            'private_level_count'=>$privet_level_assessment,
            'group_count'=>$group_count,
            'webinar_count'=>$webinar_count,
            'workshop_count'=>$workshop,
            'lang' => $lang->values()->all(),
            'near_group'=>$near_group,
            'near_private'=>$mapped,
            'near_webinar'=>$near_webinar,
            'near_work_shop'=>$near_work_shop,
            'private'=>$privet,
            'classes' => $allClasses,
            'private_level'=>$privet_level,
            'stories'=>$stories,
            'report' => $data
        ]);


    }


    public function incomeChart(Request $request)
    {
        $request->validate([
            'type' => 'required|in:weekly,monthly,yearly',
            'start_date' => 'nullable|date',
        ]);

        $user = auth()->user();
        $professorId = $user->professor->id;

        $type = $request->type;
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : now();
        $data = collect();
        if ($type === 'weekly') {
            for ($i = 6; $i >= 0; $i--) {
                $day = $startDate->copy()->subDays($i)->startOfDay();
                $nextDay = $day->copy()->endOfDay();

                $amount = $this->getTotalIncomeBetween($professorId, $day, $nextDay);
                $label = Jalalian::fromCarbon($day)->format('l'); // شنبه، یکشنبه...

                $data->push([
                    'label' => $label,
                    'amount' => $amount,
                ]);
            }

            // ترتیب صحیح روزهای هفته فارسی
            $persianWeekdays = [
                'شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه'
            ];

            $data = $data->sortBy(function ($item) use ($persianWeekdays) {
                return array_search($item['label'], $persianWeekdays);
            })->values();

        } elseif ($type === 'monthly') {
            for ($i = 0; $i < 30; $i++) {
                $day = $startDate->copy()->addDays($i)->startOfDay();
                $nextDay = $day->copy()->endOfDay();

                $amount = $this->getTotalIncomeBetween($professorId, $day, $nextDay);
                $jDate = Jalalian::fromCarbon($day);
                $label = $jDate->format('%d %B'); // مثلاً ۲۵ فروردین

                $data->push([
                    'label' => $label,
                    'amount' => $amount,
                ]);
            }
        } elseif ($type === 'yearly') {
            $persianMonths = [
                'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
                'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
            ];

            $tempData = collect();

            for ($i = 11; $i >= 0; $i--) {
                $month = $startDate->copy()->subMonths($i)->startOfMonth();
                $endOfMonth = $month->copy()->endOfMonth();

                $amount = $this->getTotalIncomeBetween($professorId, $month, $endOfMonth);
                $label = Jalalian::fromCarbon($month)->format('%B'); // مثلاً فروردین

                $tempData->push([
                    'label' => $label,
                    'amount' => $amount,
                ]);
            }

            $data = collect($persianMonths)->map(function ($monthName) use ($tempData) {
                $found = $tempData->firstWhere('label', $monthName);
                return [
                    'label' => $monthName,
                    'amount' => $found ? $found['amount'] : 0,
                ];
            });
        }

        return api_response($data, 'ok');
    }


    private function getTotalIncomeBetween($professorId, $start, $end)
    {
        $webinarIncome = WebinarReservation::whereHas('webinar', function ($q) use ($professorId) {
            $q->where('professors_id', $professorId);
        })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->with('webinar')
            ->get()
            ->sum(function ($reservation) {
                return $reservation->webinar->new_total_price ?? 0;
            });

        $workshopIncome  = WorkShopReservation::whereHas('WorkShop', function ($q) use ($professorId) {
            $q->where('professors_id', $professorId);
        })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->with('WorkShop') // اینجا اصلاح شد
            ->get()
            ->sum(function ($reservation) {
                return $reservation->WorkShop->new_total_price ?? 0;
            });

        $groupClassIncome = GroupClassReservation::whereHas('groupClass', function ($q) use ($professorId) {
            $q->where('professors_id', $professorId);
        })
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->with('groupClass')
            ->get()
            ->sum(function ($reservation) {
                return $reservation->groupClass->new_total_price ?? 0;
            });

        $privateIncome = PrivetClassReservation::where('professor_id', $professorId)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->sum(function ($reservation) {
                return $reservation->books->sum('price')  ?? 0;
            });

        return $webinarIncome + $groupClassIncome + $privateIncome + $workshopIncome;
    }

}
