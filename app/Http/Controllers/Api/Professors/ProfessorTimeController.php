<?php

namespace App\Http\Controllers\Api\Professors;

use App\Models\Professors\privetTeacherTimeSlot;
use App\Models\Professors\Professor;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Professors\TeacherTimeSlot;

class ProfessorTimeController extends Controller
{
    public function index(Request $request, $teacher_id)
    {
        $request->validate([
            'start_date' => 'required|date',
        ]);


        $startDate = $request->start_date;
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));

        $timeSlots = TeacherTimeSlot::where('teacher_id', $teacher_id)
            ->whereDate('date', '>=', today())
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('date');

        $formattedSlots = $this->formatTimeSlots($timeSlots);

        return api_response($formattedSlots);
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
                    'status' => $slot->status,
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

    public function show(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
        ]);

        $professor_id = auth()->user()->professor->id;
        $startDate = $request->start_date;
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));

        // گرفتن id های رزرو شده از جدول pivot
        $reservedSlotIds = PrivetTeacherTimeSlot::pluck('teacher_time_slot_id')->toArray();

        // دریافت تایم‌اسلات‌های آزاد
        $timeSlots = TeacherTimeSlot::where('teacher_id', $professor_id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotIn('id', $reservedSlotIds)
            ->select('id', 'date', 'time')
            ->get()
            ->map(function ($slot) {
                $slot->time = substr($slot->time, 0, 5); // بریدن مقدار به فرمت HH:MM
                return $slot;
            });

        $min = TeacherTimeSlot::where('teacher_id', $professor_id)
            ->where('date', '>=', now()->toDateString())
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('min_blocks')
            ->first() ?? 1;

        return api_response([
            'dates' => $timeSlots,
            'min_blocks' => $min
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'dates' => 'required|array',
            'dates.*.date' => 'required|date',
            'dates.*.time' => 'required|date_format:H:i',
            'week' => 'nullable|integer|min:1',
            'min_blocks' => 'nullable|integer|min:1',
        ], [], [
            'dates' => 'تاریخ‌ها',
            'dates.*.date' => 'تاریخ',
            'dates.*.time' => 'زمان',
            'week' => 'هفته',
            'min_blocks' => 'حداقل بلوک‌ها',
        ]);

        $professor_id = auth()->user()->professor->id;
        $createdSlots = [];
        $failedSlots = [];
        $allSlots = []; // آرایه‌ای برای ذخیره تایم‌های جدید و موجود
        $weekCount = $request->week ?? 1;

        foreach ($request->dates as $dateItem) {
            for ($i = 0; $i < $weekCount; $i++) {
                $newDate = Carbon::parse($dateItem['date'])->addWeeks($i)->format('Y-m-d');

                $existingSlot = TeacherTimeSlot::where('teacher_id', $professor_id)
                    ->where('date', $newDate)
                    ->where('time', $dateItem['time'])
                    ->first();

                if ($existingSlot) {
                    // اضافه کردن تایم موجود به آرایه $allSlots
                    $allSlots[] = $existingSlot->id;
                    $failedSlots[] = [
                        'date' => $newDate,
                        'time' => $dateItem['time'],
                        'error' => 'Time slot already exists.',
                    ];
                    continue;
                }

                try {
                    // ایجاد تایم جدید
                    $timeSlot = TeacherTimeSlot::create([
                        'teacher_id' => $professor_id,
                        'date' => $newDate,
                        'time' => $dateItem['time'],
                        'min_blocks' => $request['min_blocks'] ?? null,
                        'status' => 'open',
                    ]);
                    $createdSlots[] = $timeSlot;
                    $allSlots[] = $timeSlot->id; // ذخیره ID تایم جدید در آرایه $allSlots
                } catch (\Exception $e) {
                    $failedSlots[] = [
                        'date' => $newDate,
                        'time' => $dateItem['time'],
                        'error' => 'Failed to create time slot.',
                    ];
                }
            }
        }

        // حذف تایم‌هایی که در دیتابیس هستند ولی در آرایه $allSlots نیستند
        TeacherTimeSlot::where('teacher_id', $professor_id)
            ->whereNotIn('id', $allSlots)
            ->delete();

        return api_response([
            'created' => $createdSlots,
            'failed' => $failedSlots,
        ]);
    }


    public function update(Request $request, $id)
    {
        $timeSlot = TeacherTimeSlot::findOrFail($id);

        $request->validate([
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i',
            'min_blocks' => 'nullable|integer|min:1',
            'status' => 'nullable|in:open,reserved,inactive',
        ]);

        $timeSlot->update($request->all());

        return response()->json($timeSlot);
    }

    public function destroy($id)
    {
        $timeSlot = TeacherTimeSlot::findOrFail($id);
        $timeSlot->delete();
        return response()->json(['message' => 'Time slot deleted successfully.'], 200);
    }
}
