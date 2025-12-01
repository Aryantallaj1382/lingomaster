<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\Professors\GroupClass;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Webinar;
use App\Models\Professors\WorkShop;
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
        $professor = auth()->user()->professor;

        // دریافت روزهای ماه شمسی از تاریخ ورودی (میلادی)
        $jalaliDays = $this->getJalaliMonthDays($request->date);

        // بررسی خطا در دریافت روزهای ماه
        if (isset($jalaliDays['error'])) {
            return api_response(null, false, $jalaliDays['error']);
        }

        $now = Carbon::now('Asia/Tehran')->startOfDay(); // زمان فعلی برای مقایسه

        $days = [];

        // پیمایش روزهای ماه شمسی
        foreach ($jalaliDays as $jalaliDayInfo) {
            $jalaliDateStr = is_array($jalaliDayInfo) ? $jalaliDayInfo['date'] : $jalaliDayInfo;
            $jalali = Jalalian::fromFormat('Y-m-d', $jalaliDateStr);
            $gregorianDate = $jalali->toCarbon()->toDateString(); // تبدیل به تاریخ میلادی
            $dayName = $weekDaysFa[$jalali->getDayOfWeek()];

            $classes = [];

            // کلاس‌های گروهی
            if ($type === 'all' || $type === 'group') {
                $groupClasses = GroupClass::with('schedules')
                    ->where('professors_id', $professor->id)
                    ->whereDate('start_date', '<=', $gregorianDate)
                    ->whereDate('end_date', '>=', $gregorianDate)
                    ->get();

                foreach ($groupClasses as $groupClass) {
                    foreach ($groupClass->schedules as $schedule) {
                        if ($schedule->day === $dayName) {
                            $classes[] = [
                                'type' => 'کلاس گروهی',
                                'title' => $groupClass->subject->title ?? 'بدون عنوان',
                                'time' => $schedule->start_time . ' تا ' . $schedule->end_time,
                                'teachingType' => $groupClass->teaching_type_id ,
                                'class_link' => $groupClass->class_link ,
                            ];
                        }
                    }
                }
            }

            // کلاس‌های خصوصی
            if ($type === 'all' || $type === 'private') {
                $privateClasses = PrivetClassReservation::where('professor_id', $professor->id)
                    ->whereHas('teacherTimeSlots', fn($q) => $q->whereDate('date', $gregorianDate))
                    ->with(['teacherTimeSlots' => fn($q) => $q->whereDate('date', $gregorianDate)])
                    ->get();

                foreach ($privateClasses as $reservation) {
                    foreach ($reservation->teacherTimeSlots as $slot) {
                        $classes[] = [
                            'type' => 'کلاس خصوصی',
                            'title' => $reservation->subject->title ?? 'بدون عنوان',
                            'time' => $slot->time,
                            'teachingType' => $reservation->teaching_type_id ,
                            'class_link' => $reservation->class_link ,
                        ];
                    }
                }
            }

            // وبینارها
            if ($type === 'all' || $type === 'webinar') {
                $webinars = Webinar::where('professors_id', $professor->id)
                    ->whereDate('date', $gregorianDate)
                    ->get();

                foreach ($webinars as $webinar) {
                    $classes[] = [
                        'type' => 'وبینار',
                        'title' => $webinar->subject->title ?? 'بدون عنوان',
                        'time' => $webinar->start_time . ' تا ' . $webinar->end_time,
                        'teachingType' => 2 ,

                    ];
                }
            }

            // ورکشاپ‌ها
            if ($type === 'all' || $type === 'workshop') {
                $workshops = Workshop::where('professors_id', $professor->id)
                    ->whereDate('date', $gregorianDate)
                    ->get();

                foreach ($workshops as $workshop) {
                    $classes[] = [
                        'type' => 'ورکشاپ',
                        'title' => $workshop->subject->title ?? 'بدون عنوان',
                        'time' => $workshop->start_time . ' تا ' . $workshop->end_time,
                        'teachingType' =>1,
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
        $professor = auth()->user()->professor;

        // تبدیل تاریخ میلادی به شمسی
        $gregorianDate = Carbon::parse($request->date); // تاریخ میلادی (مثلاً 2025-04-27)
        $jalaliDate = Jalalian::fromCarbon($gregorianDate); // تبدیل به شمسی (1404-02-07)

        // پیدا کردن اولین روز هفته (شنبه)
        $dayOfWeek = $jalaliDate->getDayOfWeek(); // 0 = شنبه، 1 = یک‌شنبه، ...
        $startOfWeek = $jalaliDate->subDays($dayOfWeek); // به ابتدای هفته (شنبه) می‌رویم

        $classes = []; // اینجا بیار بیرون حلقه

        for ($i = 0; $i < 7; $i++) {
            $currentDay = $startOfWeek->addDays($i);
            $jalaliDateStr = $currentDay->format('Y-m-d');
            $gregorianDate = $currentDay->toCarbon()->toDateString();
            $dayName = $weekDaysFa[$currentDay->getDayOfWeek()];

            // 🎯 کلاس‌های گروهی
            if ($type === 'all' || $type === 'group') {
                $groupClasses = GroupClass::with('schedules')
                    ->where('professors_id', $professor->id)
                    ->whereDate('start_date', '<=', $gregorianDate)
                    ->whereDate('end_date', '>=', $gregorianDate)
                    ->get();
                foreach ($groupClasses as $groupClass) {
                    foreach ($groupClass->schedules as $schedule) {
                        if ($schedule->day === $dayName) {
                            $start = Carbon::createFromTimeString($schedule->start_time);
                            $end = Carbon::createFromTimeString($schedule->end_time);

                            $sessionNumber = 1; // شماره جلسه در همان روز خاص

                            while ($start < $end) {
                                $next = $start->copy()->addMinutes(30);
                                if ($next > $end) {
                                    break;
                                }

                                $classes[] = [
                                    'type' => 'کلاس گروهی',
                                    'title' => $groupClass->subject->title ?? 'بدون عنوان',
                                    'time' => $start->format('H:i'),
                                    'date' => $gregorianDate,
                                    'session' => $sessionNumber,
                                    'past' => $gregorianDate > now() ? false : true,
                                    'teachingType' => $groupClass->teaching_type_id ,
                                    'class_link' => $groupClass->class_link ?? null ,
                                ];

                                $start = $next;
                                $sessionNumber++; // افزایش شماره جلسه
                            }
                        }
                    }
                }
            }

            // 🎯 کلاس‌های خصوصی
            if ($type === 'all' || $type === 'private') {
                $privateClasses = PrivetClassReservation::where('professor_id', $professor->id)
                    ->whereHas('teacherTimeSlots', fn($q) => $q->whereDate('date', $gregorianDate))
                    ->with(['teacherTimeSlots' => fn($q) => $q->whereDate('date', $gregorianDate)])
                    ->get();

                foreach ($privateClasses as $reservation) {
                    $sortedSlots = $reservation->teacherTimeSlots->sortBy(fn($slot) => $slot->date . ' ' . $slot->time)->values();

                    foreach ($sortedSlots as $index => $slot) {
                        $classes[] = [
                            'type' => 'کلاس خصوصی',
                            'title' => $reservation->title ?? 'بدون عنوان',
                            'time' => $slot->time,
                            'date' => $slot->date,
                            'session' => $index + 1, // شماره جلسه
                            'past' => $slot->date > now() ? false : true,
                            'teachingType' => $reservation->teaching_type_id ,
                            'class_link' => $reservation->class_link ,
                        ];
                    }
                }

            }

            // 🎯 وبینار
            if ($type === 'all' || $type === 'webinar') {
                $webinars = Webinar::where('professors_id', $professor->id)
                    ->whereDate('date', $gregorianDate)
                    ->get();

                foreach ($webinars as $webinar) {
                    $start = Carbon::createFromFormat('H:i', $webinar->hour);
                    for ($d = 0; $d < $webinar->time_count; $d++) {
                        $from = $start->copy()->addMinutes($d * 30);
                        $to = $from->copy()->addMinutes(30);

                        $classes[] = [
                            'type' => 'وبینار',
                            'title' => $webinar->subject->title ?? 'بدون عنوان',
                            'time' => $from->format('H:i') ,
                            'date' => $webinar->date,
                            'past' => $webinar->date > now() ? false : true,
                            'teachingType' => 2 ,
                            'class_link' => null,
                        ];
                    }
                }
            }

            // 🎯 ورکشاپ
            if ($type === 'all' || $type === 'workshop') {
                $workshops = WorkShop::where('professors_id', $professor->id)
                    ->whereDate('date', $gregorianDate)
                    ->get();

                foreach ($workshops as $workshop) {
                    $start = Carbon::createFromFormat('H:i', $workshop->hour);
                    for ($d = 0; $d < $workshop->time_count; $d++) {
                        $from = $start->copy()->addMinutes($d * 30);
                        $to = $from->copy()->addMinutes(30);

                        $classes[] = [
                            'type' => 'ورکشاپ',
                            'title' => $workshop->subject->title ?? 'بدون عنوان',
                            'time' => $from->format('H:i'),
                            'date' => $workshop->date,
                            'past' => $workshop->date > now() ? false : true,

                        ];
                    }
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
        $professor = auth()->user()->professor;
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
            $groupClasses = GroupClass::with('schedules')
                ->where('professors_id', $professor->id)
                ->whereDate('start_date', '<=', $gregorianDate)
                ->whereDate('end_date', '>=', $gregorianDate)
                ->get();

            foreach ($groupClasses as $groupClass) {
                foreach ($groupClass->schedules as $schedule) {
                    if ($schedule->day === $dayName) {
                        $start = Carbon::createFromTimeString($schedule->start_time);
                        $end = Carbon::createFromTimeString($schedule->end_time);

                        $sessionNumber = 1; // شماره جلسه در همان روز خاص

                        while ($start < $end) {
                            $next = $start->copy()->addMinutes(30);
                            if ($next > $end) {
                                break;
                            }

                            $classes[] = [
                                'type' => 'کلاس گروهی',
                                'title' => $groupClass->subject->title ?? 'بدون عنوان',
                                'time' => $start->format('H:i'),
                                'date' => $gregorianDate,
                                'session' => $sessionNumber,
                                'past' => $gregorianDate > now() ? false : true,
                            ];

                            $start = $next;
                            $sessionNumber++; // افزایش شماره جلسه
                        }
                    }
                }
            }
        }

        // 🎯 کلاس‌های خصوصی
        if ($type === 'all' || $type === 'private') {
            $privateClasses = PrivetClassReservation::where('professor_id', $professor->id)
                ->whereHas('teacherTimeSlots', fn($q) => $q->whereDate('date', $gregorianDate))
                ->with(['teacherTimeSlots' => fn($q) => $q->whereDate('date', $gregorianDate)])
                ->get();

            foreach ($privateClasses as $reservation) {
                $sortedSlots = $reservation->teacherTimeSlots->sortBy(fn($slot) => $slot->date . ' ' . $slot->time)->values();

                foreach ($sortedSlots as $index => $slot) {
                    $classes[] = [
                        'type' => 'کلاس خصوصی',
                        'title' => $reservation->title ?? 'بدون عنوان',
                        'time' => $slot->time,
                        'date' => $slot->date,
                        'session' => $index + 1, // شماره جلسه
                        'past' => $slot->date > now() ? false : true,
                        'teachingType' => $groupClass->teaching_type_id ,
                        'class_link' => $groupClass->class_link ?? null ,
                    ];
                }
            }
        }

        // 🎯 وبینار
        if ($type === 'all' || $type === 'webinar') {
            $webinars = Webinar::where('professors_id', $professor->id)
                ->whereDate('date', $gregorianDate)
                ->get();

            foreach ($webinars as $webinar) {
                $start = Carbon::createFromFormat('H:i', $webinar->hour);
                for ($d = 0; $d < $webinar->time_count; $d++) {
                    $from = $start->copy()->addMinutes($d * 30);

                    $classes[] = [
                        'type' => 'وبینار',
                        'title' => $webinar->subject->title ?? 'بدون عنوان',
                        'time' => $from->format('H:i'),
                        'date' => $webinar->date,
                        'past' => $webinar->date > now() ? false : true,
                        'teachingType' =>2,
                        'class_link' =>  null ,
                    ];
                }
            }
        }

        // 🎯 ورکشاپ
        if ($type === 'all' || $type === 'workshop') {
            $workshops = WorkShop::where('professors_id', $professor->id)
                ->whereDate('date', $gregorianDate)
                ->get();

            foreach ($workshops as $workshop) {
                $start = Carbon::createFromFormat('H:i', $workshop->hour);
                for ($d = 0; $d < $workshop->time_count; $d++) {
                    $from = $start->copy()->addMinutes($d * 30);

                    $classes[] = [
                        'type' => 'ورکشاپ',
                        'title' => $workshop->subject->title ?? 'بدون عنوان',
                        'time' => $from->format('H:i'),
                        'date' => $workshop->date,
                        'past' => $workshop->date > now() ? false : true,

                        'teachingType' =>1,
                        'class_link' => null ,

                    ];
                }
            }
        }

        return api_response($classes);
    }

}
