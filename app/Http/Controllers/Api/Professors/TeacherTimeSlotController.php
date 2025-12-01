<?php

namespace App\Http\Controllers\Api\Professors;

use App\Models\Professors\Professor;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Professors\TeacherTimeSlot;

class TeacherTimeSlotController extends Controller
{
    public function formatTimeSlots($timeSlots)
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

    public function index(Request $request, $teacher_id)
    {
        $request->validate([
            'start_date' => 'required|date',
        ]);

        if (!Professor::where('id', $teacher_id)->exists()) {
            return response()->json(['error' => 'Teacher not found.'], 404);
        }

        $startDate = $request->start_date;
        $endDate = date('Y-m-d', strtotime($startDate . ' +6 days'));

        $timeSlots = TeacherTimeSlot::where('teacher_id', $teacher_id)
            ->where('date','>',now())
            ->whereBetween('date', [$startDate, $endDate])
            ->get()
            ->groupBy('date');

        $formattedSlots = $this->formatTimeSlots($timeSlots);

        return api_response($formattedSlots);
    }



    public function store(Request $request, $teacher_id)
    {
        $request->validate([
            'date' => 'required|date',
            'time' => 'required|date_format:H:i',
            'min_blocks' => 'required|integer|min:1',
        ]);

        if (!User::where('id', $teacher_id)->where('role', 'teacher')->exists()) {
            return response()->json(['error' => 'Teacher not found or invalid role.'], 404);
        }

        $existingSlot = TeacherTimeSlot::where('teacher_id', $teacher_id)
            ->where('date', $request->date)
            ->where('time', $request->time)
            ->first();

        if ($existingSlot) {
            return response()->json(['error' => 'Time slot already exists.'], 409);
        }

        try {
            $timeSlot = TeacherTimeSlot::create([
                'teacher_id' => $teacher_id,
                'date' => $request->date,
                'time' => $request->time,
                'min_blocks' => $request->min_blocks,
                'status' => 'inactive',
            ]);

            return response()->json($timeSlot, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create time slot.'], 500);
        }
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
