<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Professors\BookLearning;
use App\Models\Professors\PrivetClassBook;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\privetTeacherTimeSlot;
use App\Models\Professors\TeacherTimeSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PrivetClassReservationController extends Controller
{

    public function index(Request $request)
    {
        $id = auth()->user()->professor->id;

        $status = $request->input('status');
        $privetClasses = PrivetClassReservation::where('professor_id', $id)
            ->where('status', $status)
            ->with(['student', 'teacherTimeSlots', 'language', 'subject', 'teachingType', 'Offline'])
            ->paginate(10);

        $privetClasses->getCollection()->transform(function ($privetClass) {
            $firstSlot = $privetClass->teacherTimeSlots->where('date', '>=', now())->sortBy('date')->first();
            $time = optional($firstSlot)->time ? substr($firstSlot->time, 0, 5) : null;
            $end_time = $time ? date('H:i', strtotime($time . ' +25 minutes')) : null;
            $full_time = $time && $end_time ? "$time - $end_time" : null;

            return [
                'image' => $privetClass->student->profile ? api::api . $privetClass->student->profile : null,
                'id' => $privetClass->id,
                'name' => $privetClass->student->first_name . ' ' . $privetClass->student->last_name,
                'student_id' => $privetClass->student->id,
                'language' => $privetClass->language->title,
                'subject' => $privetClass->subject->title,
                'date' => $firstSlot->date ?? null,
                'time' => $full_time,
                'teaching_type' => $privetClass->teaching_type_id === 1
                    ? $privetClass->teachingType->title . ' ' . ('منطقه ' . (optional($privetClass->Offline)->title ?? 'نامشخص'))
                    : $privetClass->teachingType->title . ' ' . ($privetClass->platform),
                'teaching_type_id' => $privetClass->teachingType->id,
            ];
        });

        return api_response($privetClasses, 'لیست کلاس های خصوصی با موفقیت بارگذاری شد');
    }

    public function show(Request $request,$id)
    {

$id_p = auth()->user()->professor->id;
        $privetClasses = PrivetClassReservation::where('professor_id',$id_p)->with([
            'student',
            'teacherTimeSlots',
            'teachingType',
            'language',
            'Offline.city',
            'ageGroup',
            'languageLevel',
            'subject.goal',
            'books'
        ])->find($id);

        if (!$privetClasses) {
            return response()->json(['message' => 'کلاس یافت نشد'], 404);
        }

        $type = $privetClasses->teaching_type_id === 1
            ? $privetClasses->teachingType->title
            : $privetClasses->teachingType->title . ' ' . ($privetClasses->platform);

        $timeSlotCount = $privetClasses->teacherTimeSlots->count();

        if ($timeSlotCount > 1) {
            $timeSlots = [
                'date' => optional($privetClasses->teacherTimeSlots->first())->date,
                'time' => optional($privetClasses->teacherTimeSlots->first())->time ? substr($privetClasses->teacherTimeSlots->first()->time, 0, 5) . ' - ' . date('H:i', strtotime(substr($privetClasses->teacherTimeSlots->first()->time, 0, 5) . ' +25 minutes')) : null,

            ];
        } else {
            $timeSlots = [
           'date' => optional($privetClasses->teacherTimeSlots->first())->date,
           'time' => optional($privetClasses->teacherTimeSlots->first())->time ? substr($privetClasses->teacherTimeSlots->first()->time, 0, 5) . ' - ' . date('H:i', strtotime(substr($privetClasses->teacherTimeSlots->first()->time, 0, 5) . ' +25 minutes')) : null,
            ];
        }

        $nearestTimeSlot = $privetClasses->teacherTimeSlots->sortBy('date')->first();
        $isNear = 0;

        if ($nearestTimeSlot) {
            $dateTime = Carbon::parse($nearestTimeSlot->date . ' ' . $nearestTimeSlot->time);
            $hoursDiff = now()->diffInHours($dateTime, false);

            if ($hoursDiff > 0 && $hoursDiff < 24) {
                $isNear = 1;
            }
        }



        $book = PrivetClassBook::where('privet_class_id', '=', $id)->value('book_learning_id');
        $books = BookLearning::where('id', $book)->get();
        $status = match ($privetClasses->status) {
            'reserved' => 0,
            'progress' => 1,
            'completed' => 2,
            'canceled' => 3,
            default => -1, // مقدار پیش‌فرض برای موارد ناشناخته
        };

        $privetClass = [
            'name' => $privetClasses->student->first_name . ' ' . $privetClasses->student->last_name,
            'student_id' => $privetClasses->student->id,

            'student_age' => $privetClasses->student->age,
            'language' => $privetClasses->language->title,
            'time_slots' => $timeSlots,
            'teaching_type' => $type,
            'status'=>$status,
            'latitude' => optional($privetClasses->Offline)->latitude,
            'longitude' => optional($privetClasses->Offline)->longitude,
            'city' => optional(optional($privetClasses->Offline)->city)->name . ' ' . optional($privetClasses->Offline)->region,
            'age_group' => $privetClasses->ageGroup->title,
            'price' => $privetClasses->price,
            'session_count' => $privetClasses->session_count,
            'language_level' => $privetClasses->languageLevel->title,
            'subject' => $privetClasses->subject->goal->title . ' ' . ($privetClasses->subject->title),
            'massage' => $privetClasses->massage,
            'teaching_type_id' => $privetClasses->teachingType->id,
            'time_slot_status' => $timeSlotCount > 1 ? 1 : 0,
            'is_near' => $isNear,
            'books' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language->title,
                'subject' => $book->subgoal->title,
                'age_group' => $book->ageGroup->title,
                'language_level' => $book->languageLevel->title,
                'images' => $book->product->images->first()->path ? api::api . $book->product->images->first()->path : null,
                ]),
        ];
        if ($status == 1) {
            $privetClass['student_user_name'] = $privetClasses->student->user_name;
            $privetClass['class_link'] = $privetClasses->platform;
        }

        if ($status == 2) {
            $privetClass['is_report'] = $privetClasses->is_report;
            $privetClass['is_penalty'] = $privetClasses->is_penalty;
        }
        if ($status == 3) {
            $privetClass['delete_by'] = $privetClasses->cancellationReason->deleted_by;
            $privetClass['reason'] = $privetClasses->cancellationReason->reason;
            $privetClass['time'] =$privetClasses->cancellationReason->time;
            $privetClass['date'] =$privetClasses->cancellationReason->date ;
            $privetClass['evidence'] = api::api.$privetClasses->cancellationReason->evidence;
        }
        if ($privetClasses->is_penalty === 1) {
            $privetClass['time'] = $privetClasses->penalty->time;
            $privetClass['date'] = $privetClasses->penalty->date;
            $privetClass['price'] = $privetClasses->penalty->price;
            $privetClass['penalty_for'] = $privetClasses->penalty->penalty_for;
            $privetClass['penalty_from'] = $privetClasses->penalty->penalty_from;
        }
        return api_response($privetClass, 'اطلاعات کلاس خصوصی با موفقیت بارگذاری شد');
    }
    public function update_link(Request $request, $id)
    {
        $request->validate([
            'class_link' => 'required',
        ], [], [
            'class_link' => 'لینک کلاس',
        ]);

        $class = PrivetClassReservation::find($id);

        $class->update([
            'class_link' => request('class_link'),
        ]);
        return api_response([], 'لینک کلاس با موفقیت ثبت شد');
    }

    public function update_cancellation(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required',
            'evidence' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ], [], [
            'reason' => 'دلیل',
            'evidence' => 'مدرک',
        ]);


        $class = PrivetClassReservation::find($id);
        if (!$class) {
            return api_response([], 'کلاس مورد نظر یافت نشد', 404);
        }

        if ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
            $filename = time() . '_' . $file->getClientOriginalName(); // نام‌گذاری فایل
            $file->move(public_path('evidence'), $filename); // انتقال به پوشه public/evidence
            $evidencePath = 'evidence/' . $filename;
        } else {
            $evidencePath = optional($class->cancellationReason)->evidence;
        }

        // اگر دلیل لغو قبلاً ثبت نشده، ایجاد کن
        if (!$class->cancellationReason) {
            $class->cancellationReason()->create([
                'privet_id' => $id,
                'deleted_by' => 'استاد',
                'reason' => $request->input('reason'),
                'time' => now()->format('H:i'),
                'date' => now()->format('Y-m-d'),
                'evidence' => $evidencePath,
            ]);
        } else {
            // اگر قبلاً وجود دارد، آن را به‌روزرسانی کن
            $class->cancellationReason->update([
                'reason' => $request->input('cancellation'),
                'evidence' => $evidencePath,
            ]);
        }

        // وضعیت کلاس را به لغو شده تغییر بده
        $class->update(['status' => 'canceled']);

        return api_response([], 'دلیل لغو با موفقیت ثبت شد');
    }

    public function calender(Request $request,$id)
    {

        $request->validate([
            'start_date' => 'required|date',
        ], [], [
            'start_date' => 'تاریخ شروع',
        ]);

        $startDate = $request->input('start_date', date('Y-m-d'));
        $teacher_id = auth()->user()->professor->id;
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));
        $times = privetTeacherTimeSlot::where('privet_class_reservation_id',$id)->pluck('teacher_time_slot_id')->toArray();
        $timeSlots = TeacherTimeSlot::where('teacher_id', $teacher_id)
            ->whereIn('id',$times)
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('date');
        $e = $this->formatTimeSlots($timeSlots);
        return api_response($e , 'ok');
    }

    private function formatTimeSlots($timeSlots)
    {
        $daysOfWeek = [
            'Saturday' => 'شنبه',
            'Sunday' => 'یکشنبه',
            'Monday' => 'دوشنبه',
            'Tuesday' => 'سه‌شنبه',
            'Wednesday' => 'چهارشنبه',
            'Thursday' => 'پنجشنبه',
            'Friday' => 'جمعه',
        ];

        $orderedDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];
        $formattedOutput = [];

        foreach ($timeSlots as $date => $slots) {
            $dayOfWeekEnglish = date('l', strtotime($date));
            $dayOfWeekPersian = $daysOfWeek[$dayOfWeekEnglish];
            if (!isset($formattedOutput[$dayOfWeekPersian])) {
                $formattedOutput[$dayOfWeekPersian] = [];
            }

            foreach ($slots as $slot) {
                $formattedOutput[$dayOfWeekPersian][] = [
                    'id' => $slot->id,
                    'time' => substr($slot->time, 0, 5),
                    'date' => $slot->date,

                ];
            }
        }

        $sortedOutput = [];
        foreach ($orderedDays as $day) {
            if (isset($formattedOutput[$day])) {
                $sortedOutput[$day] = $formattedOutput[$day];
            }
        }

        return $sortedOutput;
    }




}
