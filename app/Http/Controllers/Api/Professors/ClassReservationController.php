<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\Professors\Professor;
use App\Models\Professors\SessionSelection;
use App\Models\Professors\TeacherTimeSlot;
use Illuminate\Http\Request;

class ClassReservationController extends Controller
{
    public function index(Professor $professor)
    {
        $professor->load([
            'languages:title,id,flag',
            'ageGroups',
            'languageLevels',
            'teachingTypes',
            'teachingTypes'
        ]);

        $sessionSelections = SessionSelection::with('affordableClasses:id,title,session_selection_id', 'assessmentTypes:id,title,session_selection_id')->get();


        $sessionSelections = $sessionSelections->map(function ($sessionSelection) {
            $data = [
                'id' => $sessionSelection->id,
                'title' => $sessionSelection->name,
            ];

            $contant = [];

            if ($sessionSelection->affordableClasses->isNotEmpty()) {
                $contant = array_merge($contant, $sessionSelection->affordableClasses->toArray());
            }

            if ($sessionSelection->assessmentTypes->isNotEmpty()) {
                $contant = array_merge($contant, $sessionSelection->assessmentTypes->toArray());
            }

            if (!empty($contant)) {
                $data['contant'] = $contant;
            }

            return $data;
        });

        $teachingTypes = $professor->teachingTypes()->with('Offline', 'online')->get();
        $teachingTypes = $teachingTypes->map(function ($teachingType) {
            $data = [
                'id' => $teachingType->id,
                'title' => $teachingType->title,
            ];

            $contant = [];

            if ($teachingType->online->isNotEmpty()) {
                $contant = array_merge($contant, $teachingType->online->toArray());
            }

            if ($teachingType->offline->isNotEmpty()) {
                $contant = array_merge($contant, $teachingType->offline->toArray());
            }

            if (!empty($contant)) {
                $data['contant'] = $contant;
            }

            return $data;
        });



        $data = [
            'sessionSelections' => $sessionSelections,
            'languages' => $professor->languages,
            'ageGroups' => $professor->ageGroups,
            'languageLevels' => $professor->languageLevels,
            'teachingTypes' => $teachingTypes,
        ];

        return api_response($data);
    }


    public function index2(Request $request)
    {
        $timeSlots = TeacherTimeSlot::withCount('privetClassReservations')->get();

        $periods = $this->definePeriods();

        $formattedSlots = $this->formatSlotsWithReservations($timeSlots, $periods);

        $dailyReservations = $this->calculateWeeklyReservations($timeSlots);

        return api_response([
            'periods' => $formattedSlots,
            'daily_reservations' => $dailyReservations,
        ]);
    }

    private function formatSlotsWithReservations($timeSlots, $periods)
    {
        $formattedOutput = [];
        $maxReservations = 0;

        // ابتدا مقادیر رزرو را جمع‌آوری کرده و بیشترین مقدار را پیدا می‌کنیم
        $tempReservations = [];

        foreach ($periods as $period => $times) {
            foreach ($times as $time) {
                $isReserved = false;
                foreach ($timeSlots as $slot) {
                    if (substr($slot->time, 0, 5) == $time) {
                        $tempReservations[$period][$time] = $slot->reservations_count;
                        $maxReservations = max($maxReservations, $slot->reservations_count);
                        $isReserved = true;
                        break;
                    }
                }
                if (!$isReserved) {
                    $tempReservations[$period][$time] = 0;
                }
            }
        }

        if ($maxReservations > 0) {
            foreach ($tempReservations as $period => $times) {
                foreach ($times as $time => $count) {
                    $formattedOutput[$period][$time] = round(($count / $maxReservations) * 5);
                }
            }
        } else {
            $formattedOutput = $tempReservations;
        }

        return $formattedOutput;
    }


    private function calculateWeeklyReservations($timeSlots)
    {
        $dailyReservations = [
            'شنبه' => 0,
            'یکشنبه' => 0,
            'دوشنبه' => 0,
            'سه‌شنبه' => 0,
            'چهارشنبه' => 0,
            'پنجشنبه' => 0,
            'جمعه' => 0,
        ];

        foreach ($timeSlots as $slot) {
            $dayOfWeek = date('l', strtotime($slot->date));
            $persianDay = $this->getPersianDay($dayOfWeek);
            $dailyReservations[$persianDay] += $slot->reservations_count;
        }

        // پیدا کردن بیشترین مقدار رزرو
        $maxReservations = max($dailyReservations);

        // تبدیل مقادیر به بازه 0 تا 5
        if ($maxReservations > 0) {
            foreach ($dailyReservations as $day => $count) {
                $dailyReservations[$day] = round(($count / $maxReservations) * 5);
            }
        }

        return $dailyReservations;
    }


// تبدیل روز هفته انگلیسی به فارسی
    private function getPersianDay($dayOfWeek)
    {
        $days = [
            'Saturday' => 'شنبه',
            'Sunday' => 'یکشنبه',
            'Monday' => 'دوشنبه',
            'Tuesday' => 'سه‌شنبه',
            'Wednesday' => 'چهارشنبه',
            'Thursday' => 'پنجشنبه',
            'Friday' => 'جمعه',
        ];

        return $days[$dayOfWeek] ?? '';
    }

    private function definePeriods()
    {
        return [
            'بامداد' => [
                '00:00', '00:30', '01:00', '01:30', '02:00', '02:30', '03:00', '03:30', '04:00', '04:30', '05:00', '05:30'
            ],
            'صبح' => [
                '06:00', '06:30', '07:00', '07:30', '08:00', '08:30', '09:00', '09:30', '10:00', '10:30', '11:00', '11:30'
            ],
            'بعد از ظهر' => [
                '12:00', '12:30', '13:00', '13:30', '14:00', '14:30', '15:00', '15:30', '16:00', '16:30', '17:00', '17:30'
            ],
            'عصر' => [
                '18:00', '18:30', '19:00', '19:30', '20:00', '20:30'
            ],
            'شب' => [
                '21:00', '21:30', '22:00', '22:30', '23:00', '23:30'
            ],
        ];
    }



}
