<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Professors\GroupClass;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Webinar;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\WorkShop;
use App\Models\Professors\WorkShopReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class Calender extends Controller
{


    public function getJalaliMonthDays($gregorianDate)
    {
        try {
            // تبدیل تاریخ میلادی به Carbon
            $date = Carbon::parse($gregorianDate);

            // تبدیل به تاریخ جلالی
            $jalaliDate = Jalalian::fromCarbon($date);

            // دریافت سال و ماه جلالی
            $jalaliYear = $jalaliDate->getYear();
            $jalaliMonth = $jalaliDate->getMonth();

            // تعداد روزهای ماه جلالی
            $daysInMonth = $jalaliMonth <= 6 ? 31 : ($jalaliMonth <= 11 ? 30 : (Jalalian::fromCarbon($date)->isLeapYear() ? 30 : 29));

            // ساخت لیست روزهای ماه
            $days = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $days[] = Jalalian::fromFormat('Y-m-d', sprintf('%d-%02d-%02d', $jalaliYear, $jalaliMonth, $day))->format('Y-m-d');
            }

            return $days;
        } catch (\Exception $e) {
            return ['error' => 'Invalid date format or processing error: ' . $e->getMessage()];
        }
    }


    public function monthlyCalendar(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'type' => ['nullable', 'in:all,private,group,webinar,workshop'],
        ]);

        $weekDaysFa = [
            0 => 'شنبه',
            1 => 'یک‌شنبه',
            2 => 'دوشنبه',
            3 => 'سه‌شنبه',
            4 => 'چهارشنبه',
            5 => 'پنج‌شنبه',
            6 => 'جمعه',
        ];

        $type = $request->get('type', 'all');
        $student = auth()->user()->student;

        // دریافت روزهای ماه شمسی از تاریخ ورودی (میلادی)
        $jalaliDays = $this->getJalaliMonthDays($request->date);

        // بررسی خطا در دریافت روزهای ماه
        if (isset($jalaliDays['error'])) {
            return api_response(null, false, $jalaliDays['error']);
        }

        $now = Carbon::now('Asia/Tehran')->startOfDay(); // زمان فعلی برای مقایسه

        $days = [];

        foreach ($jalaliDays as $jalaliDayInfo) {
            $jalaliDateStr = is_array($jalaliDayInfo) ? $jalaliDayInfo['date'] : $jalaliDayInfo;
            $jalali = Jalalian::fromFormat('Y-m-d', $jalaliDateStr);
            $gregorianDate = $jalali->toCarbon()->toDateString(); // تبدیل به تاریخ میلادی
            $dayName = $weekDaysFa[$jalali->getDayOfWeek()];

            $classes = [];

            // 🎯 کلاس‌های خصوصی
            if ($type === 'all' || $type === 'private') {
                $privateClasses = PrivetClassReservation::where('student_id', $student->id)
                    ->whereHas('teacherTimeSlots', fn($q) => $q->whereDate('date', $gregorianDate))
                    ->with(['teacherTimeSlots' => fn($q) => $q->whereDate('date', $gregorianDate)])
                    ->where('payment_status', true)
                    ->get();

                foreach ($privateClasses as $reservation) {
                    foreach ($reservation->teacherTimeSlots as $slot) {
                        $classes[] = [
                            'type' => 'کلاس خصوصی',
                            'title' => $reservation->subject->title ?? 'بدون عنوان',
                            'time' => $slot->time,
                            'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                            'teachingType' => $reservation->teaching_type_id,
                            'class_link' => $reservation->class_link,
                        ];
                    }
                }
            }

            // 🎯 کلاس‌های گروهی
            if ($type === 'all' || $type === 'group') {
                $groupClasses = GroupClassReservation::with('groupClass.schedules')
                    ->where('student_id', $student->id)
                    ->whereHas('groupClass', fn($q) =>
                    $q->whereDate('start_date', '<=', $gregorianDate)
                        ->whereDate('end_date', '>=', $gregorianDate)
                    )
                    ->where('payment_status', true)
                    ->get();

                foreach ($groupClasses as $reservation) {
                    foreach ($reservation->class->schedules as $schedule) {
                        if ($schedule->day === $dayName) {
                            $classes[] = [
                                'type' => 'کلاس گروهی',
                                'title' => $reservation->class->subject->title ?? 'بدون عنوان',
                                'time' => $schedule->start_time . ' تا ' . $schedule->end_time,
                                'teachingType' => $reservation->class->teaching_type_id,
                                'class_link' => $reservation->class->class_link,
                            ];
                        }
                    }
                }
            }

            // 🎯 وبینار
            if ($type === 'all' || $type === 'webinar') {
                $webinars = WebinarReservation::where('student_id', $student->id)
                    ->whereHas('webinar', fn($q) => $q->whereDate('date', $gregorianDate))
                    ->where('payment_status', true)
                    ->get();

                foreach ($webinars as $reservation) {
                    $classes[] = [
                        'type' => 'وبینار',
                        'title' => $reservation->webinar->subject->title ?? 'بدون عنوان',
                        'time' => $reservation->webinar->start_time . ' تا ' . $reservation->webinar->end_time,
                        'teachingType' => 2,
                    ];
                }
            }

            // 🎯 ورکشاپ
            if ($type === 'all' || $type === 'workshop') {
                $workshops = WorkShopReservation::where('student_id', $student->id)
                    ->whereHas('workshop', fn($q) => $q->whereDate('date', $gregorianDate))
                    ->where('payment_status', true)
                    ->get();

                foreach ($workshops as $reservation) {
                    $classes[] = [
                        'type' => 'ورکشاپ',
                        'title' => $reservation->workshop->subject->title ?? 'بدون عنوان',
                        'time' => $reservation->workshop->start_time . ' تا ' . $reservation->workshop->end_time,
                        'teachingType' => 1,
                    ];
                }
            }

            $days[] = [
                'date' => $jalaliDateStr,
                'day_name' => $dayName,
                'past' => Carbon::parse($gregorianDate)->lt($now),
                'classes' => $classes,
            ];
        }

        return api_response($days);
    }
    public function weeklyCalendar(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'], // تاریخ میلادی با فرمت Y-m-d
            'type' => ['nullable', 'in:all,private,group,webinar,workshop'],
        ]);

        $weekDaysFa = [
            0 => 'شنبه',
            1 => 'یک‌شنبه',
            2 => 'دوشنبه',
            3 => 'سه‌شنبه',
            4 => 'چهارشنبه',
            5 => 'پنج‌شنبه',
            6 => 'جمعه',
        ];

        $type = $request->get('type', 'all');
        $student = auth()->user()->student;

        // تبدیل تاریخ میلادی به شمسی
        $gregorianDate = Carbon::parse($request->date); // تاریخ میلادی (مثلاً 2025-04-27)
        $jalaliDate = Jalalian::fromCarbon($gregorianDate); // تبدیل به شمسی (1404-02-07)

        // پیدا کردن اولین روز هفته (شنبه)
        $dayOfWeek = $jalaliDate->getDayOfWeek(); // 0 = شنبه، 1 = یک‌شنبه، ...
        $startOfWeek = $jalaliDate->subDays($dayOfWeek); // به ابتدای هفته (شنبه) می‌رویم

        $classes = [];

        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->addDays($i);
            $jalaliDateStr = $currentDay->format('Y-m-d');
            $gregorianDate = $currentDay->toCarbon()->toDateString();
            $dayName = $weekDaysFa[$currentDay->getDayOfWeek()];
            if ($type === 'all' || $type === 'group') {
                $groupClasses = GroupClassReservation::with('groupClass.schedules')
                    ->where('student_id', $student->id)
                    ->whereHas('groupClass', fn($q) =>
                    $q->whereDate('start_date', '<=', $gregorianDate)
                        ->whereDate('end_date', '>=', $gregorianDate)
                    )
                    ->where('payment_status', true)
                    ->get();

                foreach ($groupClasses as $reservation) {
                    foreach ($reservation->class->schedules as $schedule) {
                        if ($schedule->day === $dayName) {
                            $classes[] = [
                                'type' => 'کلاس گروهی',
                                'title' => $reservation->class->subject->title ?? 'بدون عنوان',
                                'time' => $schedule->start_time . ' تا ' . $schedule->end_time,
                                'date' => $gregorianDate,
                                'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                                'teachingType' => $reservation->class->teaching_type_id,
                                'class_link' => $reservation->class->class_link,
                            ];
                        }
                    }
                }
            }
            if ($type === 'all' || $type === 'private') {
                $privateClasses = PrivetClassReservation::where('student_id', $student->id)
                    ->whereHas('teacherTimeSlots', fn($q) => $q->whereDate('date', $gregorianDate))
                    ->with(['teacherTimeSlots' => fn($q) => $q->whereDate('date', $gregorianDate)])
                    ->where('payment_status', true)
                    ->get();

                foreach ($privateClasses as $reservation) {
                    foreach ($reservation->teacherTimeSlots as $slot) {
                        $classes[] = [
                            'type' => 'کلاس خصوصی',
                            'title' => $reservation->subject->title ?? 'بدون عنوان',
                            'time' => $slot->time,
                            'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                            'teachingType' => $reservation->teaching_type_id,
                            'class_link' => $reservation->class_link,
                        ];
                    }
                }
            }
            // 🎯 وبینار
            if ($type === 'all' || $type === 'webinar') {
                $webinars = WebinarReservation::where('student_id', $student->id)
                    ->whereHas('webinar', fn($q) => $q->whereDate('date', $gregorianDate))
                    ->where('payment_status', true)
                    ->get();

                foreach ($webinars as $reservation) {
                    $classes[] = [
                        'type' => 'وبینار',
                        'title' => $reservation->webinar->subject->title ?? 'بدون عنوان',
                        'time' => $reservation->webinar->start_time . ' تا ' . $reservation->webinar->end_time,
                        'date' => $gregorianDate,
                        'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                        'teachingType' => 2,
                    ];
                }
            }

            // 🎯 ورکشاپ
            if ($type === 'all' || $type === 'workshop') {
                $workshops = WorkShopReservation::where('student_id', $student->id)
                    ->whereHas('workshop', fn($q) => $q->whereDate('date', $gregorianDate))
                    ->where('payment_status', true)
                    ->get();

                foreach ($workshops as $reservation) {
                    $classes[] = [
                        'type' => 'ورکشاپ',
                        'title' => $reservation->workshop->subject->title ?? 'بدون عنوان',
                        'time' => $reservation->workshop->start_time . ' تا ' . $reservation->workshop->end_time,
                        'date' => $gregorianDate,
                        'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                        'teachingType' => 1,
                    ];
                }
            }
        }

        return api_response($classes);
    }
    public function dailyCalendar(Request $request)
    {
        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'type' => ['nullable', 'in:all,private,group,webinar,workshop'],
        ]);

        $type = $request->get('type', 'all');
        $student = auth()->user()->student;
        $gregorianDate = Carbon::parse($request->date)->toDateString(); // مثل: 2025-05-01

        $jalaliDate = Jalalian::fromCarbon(Carbon::parse($gregorianDate));
        $weekDaysFa = [
            0 => 'شنبه',
            1 => 'یک‌شنبه',
            2 => 'دوشنبه',
            3 => 'سه‌شنبه',
            4 => 'چهارشنبه',
            5 => 'پنج‌شنبه',
            6 => 'جمعه',
        ];
        $dayName = $weekDaysFa[$jalaliDate->getDayOfWeek()];

        $classes = [];

        // 🎯 کلاس‌های گروهی
        if ($type === 'all' || $type === 'group') {
            $groupClasses = GroupClassReservation::with('groupClass.schedules')
                ->where('student_id', $student->id)
                ->whereHas('groupClass', fn($q) =>
                $q->whereDate('start_date', '<=', $gregorianDate)
                    ->whereDate('end_date', '>=', $gregorianDate)
                )
                ->where('payment_status', true)
                ->get();

            foreach ($groupClasses as $reservation) {
                foreach ($reservation->class->schedules as $schedule) {
                    if ($schedule->day === $dayName) {
                        $classes[] = [
                            'type' => 'کلاس گروهی',
                            'title' => $reservation->class->subject->title ?? 'بدون عنوان',
                            'time' => $schedule->start_time . ' تا ' . $schedule->end_time,
                            'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                            'teachingType' => $reservation->class->teaching_type_id,
                            'class_link' => $reservation->class->class_link,
                        ];
                    }
                }
            }
        }
        if ($type === 'all' || $type === 'private') {
            $privateClasses = PrivetClassReservation::where('student_id', $student->id)
                ->whereHas('teacherTimeSlots', fn($q) => $q->whereDate('date', $gregorianDate))
                ->with(['teacherTimeSlots' => fn($q) => $q->whereDate('date', $gregorianDate)])
                ->where('payment_status', true)
                ->get();

            foreach ($privateClasses as $reservation) {
                foreach ($reservation->teacherTimeSlots as $slot) {
                    $classes[] = [
                        'type' => 'کلاس خصوصی',
                        'title' => $reservation->subject->title ?? 'بدون عنوان',
                        'time' => $slot->time,
                        'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                        'teachingType' => $reservation->teaching_type_id,
                        'class_link' => $reservation->class_link,
                    ];
                }
            }
        }
        // 🎯 وبینار
        if ($type === 'all' || $type === 'webinar') {
            $webinars = WebinarReservation::where('student_id', $student->id)
                ->whereHas('webinar', fn($q) => $q->whereDate('date', $gregorianDate))
                ->where('payment_status', true)
                ->get();

            foreach ($webinars as $reservation) {
                $classes[] = [
                    'type' => 'وبینار',
                    'title' => $reservation->webinar->subject->title ?? 'بدون عنوان',
                    'time' => $reservation->webinar->start_time . ' تا ' . $reservation->webinar->end_time,
                    'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                    'teachingType' => 2,
                ];
            }
        }

        // 🎯 ورکشاپ
        if ($type === 'all' || $type === 'workshop') {
            $workshops = WorkShopReservation::where('student_id', $student->id)
                ->whereHas('workshop', fn($q) => $q->whereDate('date', $gregorianDate))
                ->where('payment_status', true)
                ->get();

            foreach ($workshops as $reservation) {
                $classes[] = [
                    'type' => 'ورکشاپ',
                    'title' => $reservation->workshop->subject->title ?? 'بدون عنوان',
                    'time' => $reservation->workshop->start_time . ' تا ' . $reservation->workshop->end_time,
                    'past' => Carbon::parse($gregorianDate)->lt(Carbon::now()),
                    'teachingType' => 1,
                ];
            }
        }

        return api_response($classes);
    }
}
