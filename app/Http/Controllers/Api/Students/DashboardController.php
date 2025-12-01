<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Exam_question\ExamStudent;
use App\Models\ExamPayment\ExamPayment;
use App\Models\ExamPayment\OrderExamPayment;

use App\Models\GroupClass;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\WorkShopReservation;
use App\Models\ReportHomeWork;
use App\Models\ReportRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class DashboardController extends Controller
{
    public function index()
    {
        $student = auth()->user()->student->id;
        $user = auth()->user()->id;

        $privet_count = PrivetClassReservation::where('student_id', $student)->where('payment_status', 'paid')->count();
        $group_count = GroupClassReservation::where('student_id', $student)->where('payment_status', 'paid')->count();
        $webinar_count = WebinarReservation::where('student_id', $student)->where('payment_status', 'paid')->count();
        $workshop = WorkShopReservation::where('student_id', $student)->where('payment_status', 'paid')->count();
//        $exam = ExamStudent::where('student_id', $student)->where('payment_status', 'paid')->count();
        $exam_payment = OrderExamPayment::where('user_id', $user)->count();

        $reports = ReportRegistration::whereRelation('classReservation', 'student_id', $student)
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
                'professor'  => optional($reservation->professor)->full_name(),
                'homework'   => $home->title ?? null,
                'is_reading' => $home->is_reading ?? null,
                'homework_status'   => $home->doing_homework ?? null, // اگر از قبل تعریف نشده، این خط ارور خواهد داد
                'id'  => $home->id,
            ];
        });


        $near_group = null;

        $near_group1 = GroupClassReservation::where('student_id', $student)
            ->where('payment_status', 'paid')
            ->whereHas('groupClass', fn($q) => $q->where('start_date', '>=', now()))
            ->with('groupClass.schedules', 'groupClass.subject', 'groupClass.language')
            ->get()
            ->sortBy(fn($r) => $r->groupClass->start_date)
            ->first();

        if ($near_group1) {
            $gc = $near_group1->groupClass;
            $near_group = [
                'id' => $near_group1->id,
                'date' => $gc->start_date,
                'time' => $gc->schedules->first()?->start_time,
                'type' => 'حضوری',
                'class' => 'ورکشاپ',
                'title' => $gc->subject->title ?? '',
                'language' => $gc->language->title ?? '',
            ];
        }


        $now = now()->format('Y-m-d H:i:s');

        $near_privet = PrivetClassReservation::where('student_id', $student)
            ->where('payment_status', 'paid')

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
            'professor_id' => $near_privet->professor->id,
            'professor' => $near_privet->professor->full_name(),
            'date' => $near_privet->teacherTimeSlots->first() ? $near_privet->teacherTimeSlots->first()->date : null,
            'time' => $near_privet->teacherTimeSlots->first()
                ? (function () use ($near_privet) {
                    $timeString = trim($near_privet->teacherTimeSlots->first()->time);
                    $format = strlen($timeString) === 5 ? 'H:i' : 'H:i:s';

                    try {
                        $start = \Carbon\Carbon::createFromFormat($format, $timeString);
                        $end = $start->copy()->addMinutes(25);
                        return $start->format('H:i') . ' - ' . $end->format('H:i');
                    } catch (\Exception $e) {
                        return null; // یا هندل خاص در صورت نیاز
                    }
                })()
                : null,
            'image' => $near_privet->professor->profile ? api::api . $near_privet->professor->profile : '',
            'title' => $near_privet->subject->title ?? '',


        ] : null;

        $near_webinar1 = WebinarReservation::where('student_id', $student)
            ->where('payment_status', 'paid')
            ->whereHas('webinar', fn($q) => $q->where('date', '>=', now()))
            ->with('webinar.subject', 'webinar.language')
            ->get()
            ->sortBy(fn($r) => $r->webinar->date)
            ->first();

        $near_webinar = $near_webinar1 ? [
            'id' => $near_webinar1->id,
            'date' => $near_webinar1->webinar->date,
            'time' => $near_webinar1->webinar->hour,
            'headline' => $near_webinar1->webinar->headline,
            'platform' => $near_webinar1->webinar->platform,
            'type' => 'آنلاین',
            'class' => 'وبینار',
            'title' => $near_webinar1->webinar->subject->title ?? '',
            'language' => $near_webinar1->webinar->language->title ?? '',
        ] : null;


        $near_work_shop1 = WorkShopReservation::where('student_id', $student)
            ->where('payment_status', 'paid')
            ->whereHas('workShop', fn($q) => $q->where('date', '>=', now()))
            ->with('workShop.subject', 'workShop.language')
            ->get()
            ->sortBy(fn($r) => $r->workShop->date)
            ->first();

        $near_work_shop = $near_work_shop1 ? [
            'id' => $near_work_shop1->id,
            'date' => $near_work_shop1->workShop->date,
            'time' => $near_work_shop1->workShop->hour,
            'headline' => $near_work_shop1->workShop->headline,
            'platform' => $near_work_shop1->workShop->platform,
            'type' => 'حضوری',
            'class' => 'ورکشاپ',
            'title' => optional($near_work_shop1->workShop->subject)->title ?? '',
            'language' => optional($near_work_shop1->workShop->language)->title ?? '',
        ] : null;


        $privet = PrivetClassReservation::where('status', 'progress')->orWhere('status', 'reserved')->where('student_id', $student)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'professor_id' => $item->professor->id,
                'student_id' => $item->student->id,
                'professor_name' => $item->professor->full_name(),
                'image' => $item->professor->profile ? api::api . $item->professor->profile : null,
                'teachingType' => $item->teachingType->title,
                'teachingTypeId' => $item->teachingType->id,
                'status' => $item->status,
                'model' => 'private',
                'date' => optional($item->teacherTimeSlots->where('date', '>', now())->sortBy('date')->first())->date,
                'time' => optional($item->teacherTimeSlots->where('date', '>', now())->sortBy('date')->first())->time,
                'class' => 'خصوصی',
            ];
        });


        $group = GroupClassReservation::where('student_id', $student)->where('payment_status', 'paid')->take(3)->get()->map(function ($item) {
            return [
                'id' => $item->groupClass->id,
                'teachingType' => $item->groupClass->teachingType->title,
                'image' => $item->groupClass->professor->profile ? api::api . $item->groupClass->professor->profile : null,
                'class_link' => $item->groupClass->class_link,
                'time' => optional($item->groupClass->schedules->where('date', '>', now())->sortBy('date')->first())->time,
                'date' => optional($item->groupClass->schedules->where('date', '>', now())->sortBy('date')->first())->date,
                'status' => $item->groupClass->professor_status,
                'teachingTypeId' => $item->groupClass->teachingType->id,
                'class' => 'گروهی',
                'model' => 'group',

            ];
        });
        $group = collect($group);
        $privet = collect($privet);
        $allClasses = $group->merge($privet);
        $webinar = WebinarReservation::where('student_id', $student)
            ->where('payment_status', 'paid')
            ->with('webinar')
            ->take(3)->get()->map(function ($item) {
                if (!$item->webinar) return null;
                return [
                    'id' => $item->webinar->id,
                    'teachingType' => 'آنلاین',
                    'time' => $item->webinar->hour,
                    'class_link' => $item->webinar->class_link,
                    'date' => $item->webinar->date,
                    'status' => $item->webinar->professor_status,
                    'teachingTypeId' => '2',
                    'class' => 'وبینار',
                    'image' => $item->webinar->professor->profile ? api::api . $item->webinar->professor->profile : null,
                    'model' => 'webinar',

                ];
            })->filter();
        $workShop = WorkShopReservation::where('student_id', $student)
            ->where('payment_status', 'paid')
            ->with('workShop')
            ->take(3)->get()->map(function ($item) {
                if (!$item->workShop) return null;
                return [
                    'id' => $item->workShop->id,
                    'teachingType' => 'حضوری',
                    'time' => $item->workShop->hour,
                    'date' => $item->workShop->date,
                    'status' => $item->workShop->professor_status,
                    'teachingTypeId' => '1',
                    'class' => 'ورکشاپ',
                    'image' => $item->workShop->professor->profile ? api::api . $item->workShop->professor->profile : null,
                    'model' => 'workShop',
                ];
            })->filter();
        $workShop = collect($workShop);
        $webinar = collect($webinar);
        $web = $workShop->merge($webinar);


        return api_response([
            'private_count' => $privet_count,
            'exam_count' =>0,
            'exam_payment_count' => $exam_payment,
            'group_count' => $group_count,
            'webinar_count' => $webinar_count,
            'workshop_count' => $workshop,
            'near_group' => $near_group,
            'near_private' => $mapped,
            'near_webinar' => $near_webinar,
            'near_work_shop' => $near_work_shop,
            'private' => $privet,
            'classes' => $allClasses,
            'web' => $web,
            'report' => $data,
        ]);


    }


    public function store(Request $request, $id)
    {
        $request->validate([
            'doing_homework' => 'nullable|boolean',
            'answer' => 'nullable|file',
        ]);

        $home = ReportHomeWork::findOrFail($id);

        $data = [
            'doing_homework' => $request->doing_homework ?? 0,
        ];

        if ($request->hasFile('answer')) {
            if ($home->answer && Storage::disk('public')->exists($home->answer)) {
                Storage::disk('public')->delete($home->answer);
            }
            $path = $request->file('answer')->store('students/files', 'public');
            $data['answer'] = $path;
        }

        $home->update($data);

        return api_response([], 'ویرایش شد');
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'type' => 'nullable',
        ]);

        if ($request->type === 'group') {
            $id = GroupClass::findOrFail($id);
            $id->update([
                ''
            ]);
        }
        elseif ($request->type === 'webinar') {}
        elseif ($request->type === 'workShop') {}
        elseif ($request->type === 'private') {}

    }

}
