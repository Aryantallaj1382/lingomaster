<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Exam_question\ExamStudent;
use App\Models\PrivateTeacherTimeSlot;
use App\Models\Professors\BookLearning;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\PrivetClassBook;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\WorkShopBook;
use App\Models\Professors\WorkShopReservation;
use App\Models\ReportRegistration;
use App\Models\Student\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Morilog\Jalali\Jalalian;
use function React\Promise\all;

class StudentProfileForProfessorController extends Controller
{
    public function show($id)
    {
        $student = Student::find($id);
        $level = $student->levelAssessments()->latest('id')->value('level');

        $privet = PrivetClassReservation::where('student_id', $id)->count();
        $group = GroupClassReservation::where('student_id', $id)->count();
        $webinar = WebinarReservation::where('student_id', $id)->count();
        $privet = WorkShopReservation::where('student_id', $id)->count();

        $all_count = $privet + $group + $webinar + $privet;

        $student =[
            'id' => $student->id,
            'age' => $student->age,
            'profile' => $student->profile ? api::api.$student->profile :null,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'completed_group_classes' => $student->completed_group_classes,
            'completed_private_classes' => $student->completed_private_classes,
            'completed_webinars' => $student->completed_webinars,
            'completed_workshops' => $student->completed_workshops,
            'completed_placement_tests' => $student->completed_placement_tests,
            'completed_plus_exams' => $student->completed_plus_exams,
            'completed_class_exams' => $student->completed_class_exams,
            'level' =>$level,
            'all_count_class' =>$all_count,
            'all_count_test' =>0,
            'create_at' => Jalalian::fromDateTime($student->created_at)->format('Y/m/d'),
        ];

        return api_response($student);
    }

    public function class(Request $request,$id)
    {
        $type = $request->input('type');
        $prof  = auth()->user()->professor->id;
        if ($type == 'private') {
            $r = PrivetClassReservation::where('student_id', $id)->where('professor_id',$prof)->paginate();
            $r->getCollection()->transform(function ($class) {
                return [
                    'id' => $class->id,
                    'professor_id' => $class->professor_id,
                    'student_id' => $class->student->id,
                    'profile' => $class->professor->profile  ? api::api.$class->professor->profile :null,
                    'name' => $class->professor->full_name(),
                    'language' => $class->language->title,
                    'session_count' => $class->session_count,
                    'subject' => $class->subject->goal->title .' '.($class->subject->title),
                    'class_type' => $class->teachingType->title . ' ' . ($class->platform ? $class->platform : $class->Offline->title),
                ];
            });


            return api_response($r);
        }
        elseif ($type == 'webinar') {
            $r = WebinarReservation::where('student_id', $id)->whereRelation('webinar','professors_id','=',$prof)->paginate();
            $r->getCollection()->transform(function ($class) {
                return [
                    'id' => $class->id,
                    'student_id' => $class->student->id,
                    'title' => $class->webinar->subject->title,
                    'webinar_id' => $class->webinar->id,
                    'professor' => $class->webinar->professor->full_name(),
                    'image' => $class->webinar->image ?? null,
                    'language' => $class->webinar->language->title,
                    'platform' => $class->webinar->platform,
                ];
            });
            return api_response($r);
        }
        elseif ($type == 'workshop') {
            $r = WorkShopReservation::where('student_id', $id)->whereRelation('WorkShop','professors_id','=',$prof)->paginate();
            $r->getCollection()->transform(function ($class) {
                return [
                    'id' => $class->id,
                    'student_id' => $class->student->id,
                    'title' => $class->WorkShop->subject->title,
                    'WorkShop_id' => $class->WorkShop->id,
                    'professor' => $class->WorkShop->professor->full_name(),
                    'image' => $class->WorkShop->image ?? null,
                    'language' => $class->WorkShop->language->title,
                    'platform' =>  $class->WorkShop->address->city->name.'_'.$class->WorkShop->address->region,
                ];
            });
            return api_response($r);
        }
        elseif ($type == 'group') {
            $r = GroupClassReservation::where('student_id', $id)->whereRelation('groupClass','professors_id','=',$prof)->paginate();
            $r->getCollection()->transform(function ($class) {
                return [
                    'id' => $class->id,
                    'title' => $class->groupClass->subject->title,
                    'student_id' => $class->student->id,
                    'groupClass_id' => $class->groupClass->id,
                    'professor' => $class->groupClass->professor->full_name(),
                    'image' => $class->groupClass->image ?? null,
                    'language' => $class->groupClass->language->title,
                    'class_type' => optional($class->groupClass->teachingType)->title . ' ' .
                        ($class->groupClass->platform
                            ? $class->groupClass->platform
                            : optional($class->groupClass->address)->title),
                ];
            });
            return api_response($r);
        }

    }



    public function calculateAverages($reservationId, $sessionId)
    {
        $data = ReportRegistration::whereHas('privateTeacherTimeSlot.classReservation', function ($query) use ($reservationId) {
            $query->where('id', $reservationId);
        })->where('private_teacher_time_slot_id', $sessionId)->get();

        $indexCount = 10;
        $sums = array_fill(0, $indexCount, 0);
        $count = $data->count();

        if ($count === 0) {
            return response()->json(array_fill(0, $indexCount, 0));
        }

        foreach ($data as $item) {
            if (!empty($item->skills)) {
                $jsonArray = $item->skills;
                if (is_string($item->skills)) {
                    $jsonArray = json_decode($item->skills, true);
                }
                if (is_array($jsonArray)) {
                    foreach ($jsonArray as $index => $value) {
                        if ($index < $indexCount && is_numeric($value)) {
                            $sums[$index] += (float)$value;
                        }
                    }
                }
            }
        }

        $averages = array_map(function ($sum) use ($count) {
            return $sum / $count;
        }, $sums);

        return api_response($averages);
    }

    public function exam(Request $request,$id)
    {
        $student = Student::find($id);

        $type = $request->input('type');
        $prof = auth()->user()->professor->id;
        if ($type == 'exam') {
            $r = ExamStudent::where('student_id', $id)->paginate();
            $r->getCollection()->transform(function ($class) {
                return [
                    'id' => $class->id,
                    'type' => 0,
                    'name' => $class->exam->name,
                    'pass' => $class->pass,
                    'created_at' => $class->created_at,
                    'score' => $class->score,
                    'file' => 'sdf',

                ];
            });
            return api_response($r);

        }
        if ($type == 'private') {
            $r = ReportRegistration::whereRelation('privateTeacherTimeSlot.classReservation', 'student_id', '=', $id)->paginate();
            $r->getCollection()->transform(function ($class) {
                return [
                    'id' => $class->id,
                    'name' => $class->privateTeacherTimeSlot->classReservation->subject->title,
                    'professor' => $class->privateTeacherTimeSlot->classReservation->professor->full_name(),
                    'exam_name' =>$class->exam_name,
                    'created_at' =>$class->created_at,
                    'pass' =>$class->student_status,
                    'score' =>$class->v,


                ];
            });
            return api_response($r);


        }
    }
    public function showClass(Request $request,$id)
    {
        $type =$request->input('type');
        if ($type == 'workshop') {
            $w = WorkShopReservation::find($id);
            return api_response([

            ]);
        }
        if ($type == 'webinar') {
            $w = WebinarReservation::find($id);
            return api_response($w);
        }
        if ($type == 'private') {
            $w = PrivetClassReservation::find($id);
            $bookIds = PrivetClassBook::where('privet_class_id', $id)->pluck('book_learning_id');
            $books = BookLearning::whereIn('id', $bookIds)->get();
            return api_response([
                'professor' => $w->professor->full_name(),
                'language' => $w->language->title,
                'session_count' =>$w->session_count,
                'ageGroup' =>$w->ageGroup->title,
                'languageLevel' =>$w->languageLevel->title,
                'teachingType' =>$w->teachingType->title,
                'subject' =>$w->subject->goal->title.' ('.$w->subject->title.')',
                'books' => $books->map(fn($book) => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'language_id' => $book->language->title,
                    'subject' => $book->subgoal->title,
                    'age_group' => $book->ageGroup->title,
                    'language_level' => $book->languageLevel->title,
                    'images' => $book->product && $book->product->images()->first()
                        ? api::api . $book->product->images()->first()->path
                        : null,
                ]),

                ]);
        }
        if ($type == 'group') {
            $w = GroupClassReservation::find($id);
            return api_response($w);
        }


    }




    public function reportByPeriod(Request $request)
    {
        // mode: session | weekly | monthly | yearly
        $mode = $request->query('mode', 'session');
        switch ($mode) {
            case 'session':
                return $this->bySession($request->query('sessionId') ,$request->query('classId'));
            case 'weekly':
                return $this->byWeekly($request->query('date', now()));
            case 'monthly':
                return $this->byMonthly($request->query('date', now()));
            case 'yearly':
                return $this->byYearly();
            default:
                return api_response([], 'Invalid mode', 400);
        }
    }

    protected function bySession(?int $sessionId, int $classId)
    {
        $query = ReportRegistration::with('privateTeacherTimeSlot')
            ->whereRelation('privateTeacherTimeSlot', 'privet_class_reservation_id', $classId);

        if ($sessionId) {
            $query->where('privet_teacher_time_slot', $sessionId);
        }

        $reg = $query->latest()->first(); // ❗️دیگر از firstOrFail استفاده نمی‌کنیم

        // گرفتن لیست تمام جلسات مربوط به کلاس
        $privetSlots = PrivateTeacherTimeSlot::with(['teacherTimeSlot', 'reportRegistrations'])
            ->where('privet_class_reservation_id', $classId)
            ->get();

        $sortedSlots = $privetSlots->sortBy(function ($slot) {
            return $slot->teacherTimeSlot->date . ' ' . $slot->teacherTimeSlot->time;
        });

        $statusArray = [];

        $index = 1;

        foreach ($sortedSlots as $slot) {
            $statusArray[] = [
                'number'     => $index++, // شماره ترتیبی از 1 شروع می‌شود
                'hasReport'  => $slot->reportRegistrations->isNotEmpty(),
                'slotId'     => $slot->id,
                'date'       => $slot->teacherTimeSlot->date,
                'time'       => $slot->teacherTimeSlot->time,
            ];
        }

        // اگر گزارشی پیدا نشد
        if (!$reg) {
            return api_response([
                'sessionId' => null,
                'date'      => null,
                'skills'    => array_fill(0, 10, 0),
                'sessions'  => array_values($statusArray),
            ]);
        }

        // اگر گزارش پیدا شد
        $skills = is_array($reg->skills) ? $reg->skills : json_decode($reg->skills, true) ?? array_fill(0, 10, 0);

        return api_response([
            'sessionId' => $reg->id,
            'date'      => $reg->created_at->toDateString(),
            'skills'    => array_slice($skills, 0, 10),
            'sessions'  => array_values($statusArray),
        ]);
    }



    protected function byWeekly($date)
    {
        $carbon = \Carbon\Carbon::parse($date);
        $start = $carbon->copy()->startOfWeek(\Carbon\Carbon::SATURDAY);
        $end   = $start->copy()->addDays(6)->endOfDay();

        $sessions = ReportRegistration::with('privateTeacherTimeSlot')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at') // مرتب‌سازی بر اساس تاریخ ایجاد
            ->get(['id', 'skills', 'created_at']);

        $result = $sessions->map(function ($reg, $index) {
            $skills = is_array($reg->skills) ? $reg->skills : json_decode($reg->skills, true) ?? array_fill(0, 10, 0);

            return [
                'number'    => $index + 1, // شماره جلسه از 1 شروع می‌شود
                'sessionId' => $reg->id,
                'date'      => $reg->created_at->toDateString(),
                'skills'    => array_slice($skills, 0, 10),
            ];
        });

        return api_response($result);
    }

    protected function byMonthly($date)
    {
        $carbon = \Carbon\Carbon::parse($date);
        $start = $carbon->copy()->startOfMonth();
        $end   = $carbon->copy()->endOfMonth();

        $sessions = ReportRegistration::with('privateTeacherTimeSlot')
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at') // مرتب‌سازی بر اساس تاریخ
            ->get(['id', 'skills', 'created_at']);

        $result = $sessions->map(function ($reg, $index) {
            $skills = is_array($reg->skills) ? $reg->skills : json_decode($reg->skills, true) ?? array_fill(0, 10, 0);

            return [
                'number'    => $index + 1, // جلسه‌ی چندم
                'sessionId' => $reg->id,
                'date'      => $reg->created_at->toDateString(),
                'skills'    => array_slice($skills, 0, 10),
            ];
        });

        return api_response($result);
    }

    protected function byYearly()
    {
        $currentYear = now()->year;

        $all = ReportRegistration::whereYear('created_at', $currentYear)
            ->get(['skills', 'created_at']);

        $stats = [];

        foreach ($all as $reg) {
            $month = $reg->created_at->month;

            if (!isset($stats[$month])) {
                $stats[$month] = [
                    'sums'  => array_fill(0, 10, 0),
                    'count' => 0,
                ];
            }

            $skills = is_array($reg->skills) ? $reg->skills : json_decode($reg->skills, true) ?? [];

            foreach ($skills as $i => $val) {
                if ($i < 10 && is_numeric($val)) {
                    $stats[$month]['sums'][$i] += (float)$val;
                }
            }

            $stats[$month]['count']++;
        }

        $persianMonths = [
            1  => 'فروردین',
            2  => 'اردیبهشت',
            3  => 'خرداد',
            4  => 'تیر',
            5  => 'مرداد',
            6  => 'شهریور',
            7  => 'مهر',
            8  => 'آبان',
            9  => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند',
        ];

        $result = [];

        foreach ($stats as $m => $info) {
            if ($info['count'] > 0) {
                $averages = array_map(
                    fn($sum) => round($sum / $info['count'], 2),
                    $info['sums']
                );

                $result[] = [
                    'month'    => $persianMonths[$m],
                    'averages' => $averages,
                ];
            }
        }

        // مرتب‌سازی نتیجه بر اساس ترتیب ماه
        usort($result, fn($a, $b) => array_search($a['month'], $persianMonths) <=> array_search($b['month'], $persianMonths));

        return api_response($result);
    }


    public function classList($id)
    {
        $class = PrivateTeacherTimeSlot::where('privet_class_reservation_id', $id)->paginate();
        $class->getCollection()->transform(function ($item, $key) {
           return [
               'number' => $key+1,
               'id'    => $item->id,
               'subject'  => $item->classReservation->subject->title,
               'professor'  => $item->classReservation->professor->full_name(),
               'date'  => $item->teacherTimeSlot->date,
               'time'  => $item->teacherTimeSlot->time,
           ];
        });

        return api_response($class);
    }





    public function show_d($id)
    {
        $report = ReportRegistration::with(['books:id,title,start,end,report_registration_id', 'homeWorks:id,title,answer,status,report_registration_id'])->where('privet_teacher_time_slot', $id)->first();
        if (!$report) {
            api_response([],'هیچ گزارش ثبت نشده');
        }
        return api_response(
            [
                'subject' => $report->privateTeacherTimeSlot->classReservation->subject->title,
                'date' => $report->privateTeacherTimeSlot->teacherTimeSlot->date,
                'time' => $report->privateTeacherTimeSlot->teacherTimeSlot->time,
                'professor' => $report->privateTeacherTimeSlot->classReservation->professor->full_name(),
                'book_star/t' => $report->books->first()->start,
                'book_end' => $report->books->first()->end,
                'book_title' => $report->books->first()->title,
                'id' => $report->id,
                'point' => $report->final_score ?? null,
                'weaknesses' => $report->weaknesses,
                'strengths' => $report->strengths,
                'skills' => $report->skills,

            ]
        );
    }

}
