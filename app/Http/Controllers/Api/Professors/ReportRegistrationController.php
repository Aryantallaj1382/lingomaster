<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\privetTeacherTimeSlot;
use App\Models\ReportBook;
use App\Models\ReportHomeWork;
use App\Models\ReportRegistration;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportRegistrationController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'private_teacher_time_slot_id' => 'required',
            'absence' => 'required|in:absence,presence,delay',
            'absence_time' => 'nullable|string',
            'exam' => 'nullable|boolean',
            'speaking' => 'nullable|integer',
            'writing' => 'nullable|integer',
            'speaking_score' => 'nullable|integer',
            'reading' => 'nullable|integer',
            'listening' => 'nullable|integer',
            'vocabulary' => 'nullable|integer',
            'final_score' => 'nullable|integer',
            'grammar' => 'nullable|integer',
            'student_status' => 'nullable|in:passed,rejected',
            'exam_solutions' => 'nullable|array',
            'strengths' => 'nullable|array',
            'weaknesses' => 'nullable|array',
            'solutions' => 'nullable|array',
            'score' => 'nullable|array',
            'skills' => 'nullable|array',
            'exam_part' => 'nullable|array',
            'class_id' => 'required',

            'bookas' => 'nullable|array',
            'bookas.*.title' => 'required|string',
            'bookas.*.start' => 'required|string',
            'bookas.*.end' => 'required|string',

            'report_home_work' => 'nullable|array',
            'report_home_work.*.title' => 'required|string',
            'report_home_work.*.answer' => 'required|string',
            'report_home_work.*.is_reading' => 'nullable',
            'report_home_work.*.status' => 'nullable|in:yes,no',
        ]);

        $report = ReportRegistration::create([
            'absence' => $validated['absence'],
            'absence_time' => $validated['absence_time'] ??null,
            'exam' => $validated['exam'] ?? false,
            'writing' => $validated['writing'] ?? null,
            'speaking' => $validated['speaking_score'] ?? null,
            'class_id' => $validated['class_id'],
            'reading' => $validated['reading'] ?? null,
            'listening' => $validated['listening'] ?? null,
            'vocabulary' => $validated['vocabulary'] ?? null,
            'final_score' => $validated['final_score'] ?? null,
            'grammar' => $validated['grammar'] ?? null,
            'student_status' => $validated['student_status'] ?? null,
            'exam_solutions' => $validated['exam_solutions'] ?? null,
            'strengths' => $validated['strengths'] ?? null,
            'weaknesses' => $validated['weaknesses'] ?? null,
            'solutions' => $validated['solutions'] ?? null,
            'score' => $validated['score'] ?? null,
            'skills' => $validated['skills'] ?? null,
            'exam_part' => $validated['exam_part'] ?? null,
            'privet_teacher_time_slot' => $validated['private_teacher_time_slot_id'],
        ]);

        if (!empty($validated['books'])) {
            foreach ($validated['books'] as $book) {
                ReportBook::create([
                    'title' => $book['title'],
                    'start' => $book['start'],
                    'end' => $book['end'],
                    'report_registration_id' => $report->id,
                ]);
            }
        }

        if (!empty($validated['report_home_work'])) {
            foreach ($validated['report_home_work'] as $homework) {
                ReportHomeWork::create([
                    'title' => $homework['title'],
                    'answer' => $homework['answer'],
                    'status' => $homework['status'] ?? null,
                    'is_reading' => $homework['is_reading'] ?? null,
                    'report_registration_id' => $report->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'گزارش با موفقیت ثبت شد.',
            'data' => $report
        ]);
    }

    public function show($id)
    {
        $report = ReportRegistration::with(['books:id,title,start,end,report_registration_id', 'homeWorks:id,title,answer,status,report_registration_id'])->where('privet_teacher_time_slot', $id)->first();
        if (!$report) {
            api_response([],'هیچ گزارش ثبت نشده');
        }
        return api_response(
       $report
        );
    }



    public function info($id)
    {
        $professor = auth()->user()->professor;

        if (!$professor) {
            return api_response([], 'شما استاد نیستید یا دسترسی ندارید');
        }

        $professorId = $professor->id;

        $Class = privetTeacherTimeSlot::findOrFail($id);
        $reservationId = $Class->privet_class_reservation_id;

// Check relation ownership properly
        $ownerMatch = $Class->whereRelation('PrivetClassReservation', 'professor_id', '=', $professorId)->exists();

        if (!$ownerMatch) {
            return api_response([], 'شما اجازه ی دسترسی ندارید');
        }

        $allSessions = privetTeacherTimeSlot::where('privet_class_reservation_id', $reservationId)
            ->join('teacher_time_slots', 'privet_teacher_time_slot.teacher_time_slot_id', '=', 'teacher_time_slots.id')
            ->orderBy('teacher_time_slots.date')
            ->orderBy('teacher_time_slots.time')
            ->select('privet_teacher_time_slot.*')
            ->get();

        $sessionNumber = $allSessions->search(function ($session) use ($Class) {
                return $session->id === $Class->id;
            }) + 1;

        $startTime = Carbon::createFromFormat('H:i:s', $Class->teacherTimeSlot->time);
        $endTime = $startTime->copy()->addMinutes(30); // مدت جلسه ۹۰ دقیقه

        $reservation = $Class->PrivetClassReservation;

        if (!$reservation) {
            return api_response([], 'رزرو خصوصی پیدا نشد');
        }

        return api_response([
            'name' => optional($reservation->student)->full_name(),
            'student_id' => optional($reservation->student)->id,
            'subject' => optional(optional($reservation->subject)->goal)->title . ' (' . optional($reservation->subject)->title . ')',
            'teachingType' => optional($reservation->teachingType)->id,
            'platform' => $reservation->platform,
            'ageGroup' => optional($reservation->ageGroup)->title,
            'languageLevel' => optional($reservation->languageLevel)->title,
            'language' => optional($reservation->language)->title,
            'session_count' => $reservation->session_count,
            'date' => optional($Class->teacherTimeSlot)->date,
            'time' => $startTime->format('H:i') . '-' . $endTime->format('H:i'),
            'session_number' => $sessionNumber,
        ]);

    }

}
