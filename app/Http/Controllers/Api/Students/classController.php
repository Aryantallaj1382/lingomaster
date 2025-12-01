<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Like;
use App\Models\Professors\BookLearning;
use App\Models\Professors\ClassPrice;
use App\Models\Professors\DiscountClassPrice;
use App\Models\Professors\GroupClass;
use App\Models\Professors\GroupClassBook;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\PrivetClassBook;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Professor;
use App\Models\Professors\Webinar;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\webinarsBook;
use App\Models\Professors\WorkShop;
use App\Models\Professors\WorkShopReservation;
use App\Models\Student\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;

class classController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');
        $id = auth()->user()->student->id;
        $private = PrivetClassReservation::where('payment_status' , 'paid')->where('student_id', $id)->where('status', $type)->take(3)->paginate(10);
        $private->getCollection()->transform(function ($item) {
            return [
                'image' => $item->professor->profile ? api::api . $item->professor->profile : null,
                'professor_id' => $item->professor->id ?? null,
                'id' => $item->id ?? null,
                'language' => $item->language->title ?? null,
                'flag' => $item->language?->icon ?? null,
                'name' => $item->professor->full_name() ?? null,
                'time' => $item->teacherTimeSlots->first()->time ?? null,
                'date' => $item->teacherTimeSlots->first()->date ?? null,
                'teaching_type' => $item->teaching_type_id === 1
                    ? $item->teachingType->title . ' ' . ('منطقه ' . (optional($item->Offline)->title ?? 'نامشخص'))
                    : $item->teachingType->title . ' ' . ($item->platform),
                'teaching_type_id' => $item->teachingType->id ?? null,
                'class_link' => $item->class_link ?? null,
            ];
        });

        return api_response($private, 'ok');

    }

    public function suggestion()
    {
        $professor = Professor::visible()->with('languages')->take(3)->get();

        $suggestions_professor = $professor->map(function ($professor) {
            $numberOfRatings = $professor->ratings()->count();
            $averageRating = $professor->ratings()->avg('rating');

            $discount = optional(
                $professor->ClassPrice->first()?->discount
            )->discount_percentage;

            if (auth()->check()) {
                $is_like = Like::where('user_id', auth()->id())
                    ->where('likeable_id', $professor->id)
                    ->where('likeable_type', Professor::class)
                    ->exists();
            }

            return [

                'profile' => $professor->profile ? api::api . $professor->profile : null,
                'name' => $professor->first_name . ' ' . $professor->last_name,
                'languages' => $professor->languages->first()->title ?? 'اینگلیسی',
                'flag' => $professor->languages->first()->icon ?? 'اینگلیسی',
                'price' => $professor->price,
                'id' => $professor->id,
                'views_count' => $professor->views_count,
                'rate' => $averageRating ?? 0,
                'rate_count' => $numberOfRatings ?? 0,
                'is_like' => $is_like ?? false,
                'off_price' => $discount,
                'percentage' => 3,


            ];
        });

        return api_response($suggestions_professor, 'با موفقیت ارسال شد', 200);
    }
    public function show_private(Request $request,$id)
    {

        $id_p = auth()->user()->student->id;
        $privetClasses = PrivetClassReservation::where('student_id',$id_p)->with([
            'student',
            'teacherTimeSlots',
            'teachingType',
            'language',
            'Offline.city',
            'ageGroup',
            'languageLevel',
            'subject.goal',
            'books',
            'privateTeacherTimeSlots.reportRegistrations',
        ])->find($id);

        $statusArray = [];
        $sortedSlots = $privetClasses->privateTeacherTimeSlots->sortBy(function ($slot) {
            return $slot->teacherTimeSlot->date . ' ' . $slot->teacherTimeSlot->time;
        });

        foreach ($sortedSlots as $pts) {
            $hasReport = $pts->reportRegistrations->isNotEmpty();

            $statusArray[] = [
                'a' => $hasReport,
                'b' => $pts->id,
            ];
        }

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
            default => -1,

        };
        $p = $privetClasses->levelAssessments ? $privetClasses->levelAssessments->count() > 0 ? 1 : 0 : 0;

        $privetClass = [
            'name' => $privetClasses->student->first_name . ' ' . $privetClasses->student->last_name,
            'student_id' => $privetClasses->student->id,

            'student_age' => $privetClasses->student->date_of_birth
                ? Carbon::parse($privetClasses->student->date_of_birth)->age
                : null,
            'language' => $privetClasses->language->title,
            'flag' => $privetClasses->language->icon,
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
            'user_id' => $privetClasses->professor->user->id,

            'is_sapt' => 0,
            'placement' => $p,
            'a'=>$statusArray,
            'books' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language->title,
                'flag' => $book->language->icon,
                'subject' => $book->subgoal->title,
                'age_group' => $book->ageGroup->title,
                'language_level' => $book->languageLevel->title,
                'images' => $book->product && $book->product->images->first()
                    ? api::api . $book->product->images->first()->path
                    : null,
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

    ///////////////// group ////////////////

    public function index_group(Request $request)
    {
        $type = $request->input('type');
        $id = auth()->user()->student->id;
        $private = GroupClassReservation::where('payment_status' , 'paid')->where('student_id', $id)->where('status', $type)->paginate(10);
        $private->getCollection()->transform(function ($item) {
            $firstSchedule = optional($item->groupClass->schedules()->first());

            return [
                'image' => $item->groupClass->iamge ? api::api . $item->groupClass->iamge : null,
                'groupClassId' => $item->groupClass->id ?? null,
                'id' => $item->groupClass->id ?? null,
                'title' => $item->groupClass->subject->title ?? null,
                'language' => $item->language->title ?? null,
                'flag' => $item->language->icon ?? null,
                'professor' => $item->groupClass->professor->full_name() ?? null,
                'day' => $item->groupClass->days . ' ' . $firstSchedule->start_time . '-' . $firstSchedule->end_time,
                'teaching_type' => $item->groupClass->teaching_type_id === 1
                    ? $item->groupClass->teachingType->title . ' ' . ('منطقه ' . (optional($item->groupClass->Offline)->title ?? 'نامشخص'))
                    : $item->groupClass->teachingType->title . ' ' . ($item->groupClass->platform),

                'teaching_type_id' => $item->groupClass->teaching_type_id,
                'start_date' => $item->groupClass->start_date,
                'end_date' => $item->groupClass->end_date,
            ];
        });

        return api_response($private, 'ok');

    }

    public function suggestion_group()
    {
        $classes = GroupClass::latest()->take(3)->get();

        $suggestions_professor = $classes->map(function ($class) {
            $numberOfRatings = $class->ratings()->count();
            $averageRating = $class->ratings()->avg('rating');


            if (auth()->check()) {
                $is_like = Like::where('user_id', auth()->id())
                    ->where('likeable_id', $class->id)
                    ->where('likeable_type', Professor::class)
                    ->exists();
            }

            return [

                'image' => $class->image,
                'title' => $class->subject->title,
                'language' => $class->language->title,
                'flag' => $class->language->icon,
                'professor' => $class->professor->first_name . ' ' . $class->professor->last_name,
                'schedules' => $class->schedules,
                'day' => $class->days . ' ' . (
                    $class->schedules->isNotEmpty()
                        ? \Carbon\Carbon::parse($class->schedules->first()->end_time)->format('H:i') . ' ' .
                        \Carbon\Carbon::parse($class->schedules->first()->start_time)->format('H:i')
                        : ''
                    ),
                'start_date' => $class->start_date,
                'end_date' => $class->end_date,
                'total_price' => $class->total_price,
                'id' => $class->id,
                'view' => $class->view,
                'rate' => $averageRating ?? 0,
                'rate_count' => $numberOfRatings ?? 0,
                'is_like' => $is_like ?? false,


            ];
        });

        return api_response($suggestions_professor, 'با موفقیت ارسال شد', 200);
    }

    public function show_group($id)
    {
        $id2 = auth()->user()->student->id;
        $class = GroupClass::whereRelation('reserve' ,'student_id','=',$id2)->with('schedules', 'books')->find($id);

        // Check if the class exists
        if (!$class) {
            return api_response(null, 'Class not found', 404); // Return an error response if not found
        }

        $book = GroupClassBook::where('group_class_id', '=', $id)->value('book_learning_id');
        $books = BookLearning::where('id', $book)->get();

        if ($class->is_professors == 1)
            $is_professor = 'استاد';
        else
            $is_professor = 'لینگومستر';

        $status = match ($class->status) {
            'reserved' => 0,
            'progress' => 1,
            'completed' => 2,
            'deleted' => 3,
            default => -1,

        };
        $mappedClass = [
            'id' => $class->id,
            'language' => $class->language->title,
            'flag' => $class->language->icon,
            'user_id' => $class->professor->user->id,

            'start_date' => $class->start_date,
            'end_date' => $class->end_date,
//            'date'=>'از'.$class->start_date.'تا'.$class->end_date,
            'schedules' => \Carbon\Carbon::parse($class->schedules->first()->end_time)->format('H:i') . '-' . \Carbon\Carbon::parse($class->schedules->first()->start_time)->format('H:i'),
            'days' => $class->days,
            'total_price' => $class->total_price,
            'teaching_type' => $class->teachingType->title,
            'address' => $class->address->address ?? null,
            'latitude' => $class->address->latitude ?? null,
            'longitude' => $class->address->longitude ?? null,
            'max_capacity' => $class->max_capacity,
            'teaching_type_id' => $class->teaching_type_id,
            'sessions_count' => $class->sessions_count,
            'ageGroup' => $class->ageGroup->title,
            'level' => $class->level->title,
            'platform' => $class->platform,
            'subject' => $class->subject->goal->title . ' (' . $class->subject->title . ')',
            'is_professor' => $is_professor,
            'class_link' => $class->class_link,
            'status' => $status, // Translate status
            'image' => $class->image,
            'books' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language->title,
                'subject' => $book->subgoal->title,
                'age_group' => $book->ageGroup->title,
                'language_level' => $book->languageLevel->title,
                'images' => $book->product->images ?? null,
            ]),
        ];

        return api_response($mappedClass, 'ok');
    }

    ///////////// webinar //////////

    public function index_webinar(Request $request)
    {
        $type = $request->input('type');
        $id = auth()->user()->student->id;
        $private = WebinarReservation::where('payment_status' , 'paid')->where('student_id', $id)->where('status', $type)->paginate(10);
        $private->getCollection()->transform(function ($item) {

            $numberOfRatings = $item->webinar->ratings()->count();
            $averageRating = $item->webinar->ratings()->avg('rating');


            if (auth()->check()) {
                $is_like = Like::where('user_id', auth()->id())
                    ->where('likeable_id', $item->webinar->id)
                    ->where('likeable_type', Professor::class)
                    ->exists();
            }

            return [
                'image' => $item->webinar->iamge ? api::api . $item->webinar->iamge : null,
                'webinarId' => $item->webinar->id ?? null,
                'title' => $item->webinar->subject->title ??  null,
                'id' => $item->webinar->id ?? null,
                'language' => $item->webinar->language->title ?? null,
                'flag' => $item->webinar->language->icon ?? null,
                'professor' => $item->webinar->professor->full_name() ?? null,
                'date' => $item->webinar->date ,
                'time' => $item->webinar->hour ,
                'rate' => $averageRating ?? 0,
                'rate_count' => $numberOfRatings ?? 0,
                'is_like' => $is_like ?? false,
                'class_link' =>$item->webinar->class_link  ? $item->webinar->class_link : null ,
            ];
        });

        return api_response($private, 'ok');

    }

    public function suggestion_webinar()
    {
        $classes = Webinar::latest()->take(3)->get();

        $suggestions_professor = $classes->map(function ($class) {
            $numberOfRatings = $class->ratings()->count();
            $averageRating = $class->ratings()->avg('rating');


            if (auth()->check()) {
                $is_like = Like::where('user_id', auth()->id())
                    ->where('likeable_id', $class->id)
                    ->where('likeable_type', Professor::class)
                    ->exists();
            }

            return [

                'image' => $class->image,
                'title' => $class->subject->title,
                'language' => $class->language->title,
                'flag' => $class->language->icon,
                'professor' => $class->professor->first_name . ' ' . $class->professor->last_name,
                'date' => $class->date ,
                'max_capacity' => $class->max_capacity ,
                'time' => $class->hour ,
                'total_price' => $class->total_price,
                'id' => $class->id,
                'view' => $class->view,
                'rate' => $averageRating ?? 0,
                'rate_count' => $numberOfRatings ?? 0,
                'is_like' => $is_like ?? false,


            ];
        });

        return api_response($suggestions_professor, 'با موفقیت ارسال شد', 200);
    }

    public function show_webinar($id)
    {
        $webinar = Webinar::find($id);

        if (!$webinar) {
            return api_response([], 'کلاس یافت نشد', 404);
        }

        $classPrice = ClassPrice::where('professor_id', $webinar->professors_id)
            ->where('language_id', $webinar->language_id)
            ->where('class_type', 'webinar') // چون کلاس گروهی است
            ->first();

        $Price = ClassPrice::where('professor_id', $webinar->professors_id)
            ->where('language_id', $webinar->language_id)
            ->where('class_type', 'webinar') // چون کلاس گروهی است
            ->value('base_price');

        if (!$classPrice) {
            return api_response(null, 'شهریه ای برای وبینار ثبت نشده است', 404);
        }

        $priceForLevel = match ($webinar->ageGroup->id) {
            1 => $classPrice->beginner_price,
            2 => $classPrice->intermediate_price,
            3 => $classPrice->advanced_price,
            default => null,
        };
        $priceForAge = match ($webinar->level->id) {
            1 => $classPrice->child_price,
            2 => $classPrice->teen_price,
            3 => $classPrice->adult_price,
            default => null,
        };
        $discount = DiscountClassPrice::where('professor_id', $webinar->professors_id)
            ->where('class_mode', 'webinar')
            ->value('discount_percentage') ?? 0;


        $total_price = $priceForAge + $priceForLevel + $Price;
        $percentage = $discount ?? 0;


        $bookIds = webinarsBook::where('webinar_id', $id)->pluck('book_learning_id');
        $books = BookLearning::whereIn('id', $bookIds)->get();
        $status = match ($webinar->professor_status) {
            'reserved' => 0,
            'finished' => 2,
            'canceled' => 1,
            default => -1,

        };
        $webinar = [
            'id' => $webinar->id,
            'language' => $webinar->language->title,
            'flag' => $webinar->language->icon,
            'user_id' => $webinar->professor->user->id,
            'subject' => $webinar->subject->goal->title . ' (' . $webinar->subject->title . ')',
            'date' => $webinar->date,
            'hour' => $webinar->hour,
            'teaching_type' => 'آنلاین',
            'teaching_type_id' => 2,
            'max_capacity' => $webinar->max_capacity,
            'min_capacity' => $webinar->min_capacity,
            'ageGroup' => $webinar->ageGroup->title,
            'gender' => $webinar->gender->title,
            'level' => $webinar->level->title,
            'status' =>$status,
            'platform' => $webinar->platform,
            'profile_discount' => $webinar->profile_discount,
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
            'is_professors' => $webinar->is_professors,
            'admin_status' => $webinar->admin_status,
            'image' => $webinar->image,
            'discount_type' => $webinar->discount->discount_type ?? null,
            'discount_percentage' => $webinar->discount->discount_percentage ?? 0,
            'total_price' => $webinar->total_price,
            'price' => $total_price,
            'class_link' =>$webinar->class_link  ? $webinar->class_link : null ,

        ];

        return api_response($webinar);
    }
    ///////////// workshop //////////

    public function index_workshop(Request $request)
    {
        $type = $request->input('type');
        $id = auth()->user()->student->id;
        $private = WorkShopReservation::where('payment_status' , 'paid')->where('student_id', $id)->where('status', $type)->paginate(10);
        $private->getCollection()->transform(function ($item) {

            return [
                'image' => $item->WorkShop->image ? api::api . $item->WorkShop->image : null,
                'WorkShopId' => $item->WorkShop->id ?? null,
                'title' => $item->WorkShop->subject->title ??  null,
                'id' => $item->WorkShop->id ?? null,
                'language' => $item->WorkShop->language->title ?? null,
                'flag' => $item->WorkShop->language->icon ?? null,
                'professor' => $item->WorkShop->professor->full_name() ?? null,
                'date' => $item->WorkShop->date ,
                'time' => $item->WorkShop->hour ,
                'class_link' =>$item->WorkShop->class_link  ? $item->webinar->class_link : null ,
            ];
        });

        return api_response($private, 'ok');

    }

    public function suggestion_workshop()
    {
        $classes = WorkShop::latest()->take(3)->get();

        $suggestions_professor = $classes->map(function ($class) {
            $numberOfRatings = $class->ratings()->count();
            $averageRating = $class->ratings()->avg('rating');


            if (auth()->check()) {
                $is_like = Like::where('user_id', auth()->id())
                    ->where('likeable_id', $class->id)
                    ->where('likeable_type', Professor::class)
                    ->exists();
            }

            return [

                'image' => $class->image,
                'title' => $class->subject->title,
                'language' => $class->language->title,
                'flag' => api::api.$class->language->flag,
                'professor' => $class->professor->first_name . ' ' . $class->professor->last_name,
                'date' => $class->date ,
                'max_capacity' => $class->max_capacity ,
                'time' => $class->hour ,
                'total_price' => $class->total_price,
                'id' => $class->id,
                'view' => $class->view,
                'rate' => $averageRating ?? 0,
                'rate_count' => $numberOfRatings ?? 0,
                'is_like' => $is_like ?? false,


            ];
        });

        return api_response($suggestions_professor, 'با موفقیت ارسال شد', 200);
    }

    public function show_workshop($id)
    {
        $webinar = WorkShop::find($id);
        $status = match ($webinar->professor_status) {
            'reserved' => 0,
            'finished' => 2,
            'canceled' => 1,
            default => -1,

        };
        if (!$webinar) {
            return api_response([], 'کلاس یافت نشد', 404);
        }

        $classPrice = ClassPrice::where('professor_id', $webinar->professors_id)
            ->where('language_id', $webinar->language_id)
            ->where('class_type', 'webinar') // چون کلاس گروهی است
            ->first();

        $Price = ClassPrice::where('professor_id', $webinar->professors_id)
            ->where('language_id', $webinar->language_id)
            ->where('class_type', 'webinar') // چون کلاس گروهی است
            ->value('base_price');

        if (!$classPrice) {
            return api_response(null, 'شهریه ای برای وبینار ثبت نشده است', 404);
        }

        $priceForLevel = match ($webinar->ageGroup->id) {
            1 => $classPrice->beginner_price,
            2 => $classPrice->intermediate_price,
            3 => $classPrice->advanced_price,
            default => null,
        };
        $priceForAge = match ($webinar->level->id) {
            1 => $classPrice->child_price,
            2 => $classPrice->teen_price,
            3 => $classPrice->adult_price,
            default => null,
        };
        $discount = DiscountClassPrice::where('professor_id', $webinar->professors_id)
            ->where('class_mode', 'webinar')
            ->value('discount_percentage') ?? 0;


        $total_price = $priceForAge + $priceForLevel + $Price;
        $percentage = $discount ?? 0;


        $bookIds = webinarsBook::where('webinar_id', $id)->pluck('book_learning_id');
        $books = BookLearning::whereIn('id', $bookIds)->get();

        $webinar = [
            'id' => $webinar->id,
            'language' => $webinar->language->title,
            'flag' => $webinar->language->icon,
            'subject' => $webinar->subject->goal->title . ' (' . $webinar->subject->title . ')',
            'date' => $webinar->date,
            'status' => $status,
            'hour' => $webinar->hour,
            'teaching_type' => 'آنلاین',
            'user_id' => $webinar->professor->user->id,

            'teaching_type_id' => 2,
            'max_capacity' => $webinar->max_capacity,
            'min_capacity' => $webinar->min_capacity,
            'ageGroup' => $webinar->ageGroup->title,
            'gender' => $webinar->gender->title,
            'level' => $webinar->level->title,
            'platform' => $webinar->platform,
            'profile_discount' => $webinar->profile_discount,
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
            'is_professors' => $webinar->is_professors,
            'admin_status' => $webinar->admin_status,
            'image' => $webinar->image,
            'discount_type' => $webinar->discount->discount_type ?? null,
            'discount_percentage' => $webinar->discount->discount_percentage ?? 0,
            'total_price' => $webinar->total_price,
            'price' => $total_price,
            'class_link' =>$webinar->class_link  ? $webinar->class_link : null ,

        ];

        return api_response($webinar);
    }


}
