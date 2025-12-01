<?php

namespace App\Http\Controllers\Api\classes;

use App\Helpers\zarinpal;
use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\DiscountForGroupClass;
use App\Models\DiscountForStudent;
use App\Models\Financial\Payment;
use App\Models\Financial\Transaction;
use App\Models\iran_regions\IranCity;
use App\Models\iran_regions\IranProvince;
use App\Models\Languages;
use App\Models\Like;
use App\Models\Professors\BookLearning;
use App\Models\Professors\Direction;
use App\Models\Professors\DiscountClass;
use App\Models\Professors\DiscountType;
use App\Models\Professors\ExperimentDiscountType;
use App\Models\Professors\Gender;
use App\Models\Professors\GroupClass;
use App\Models\Professors\GroupClassBook;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\GroupClassSchedule;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningGoal;
use App\Models\Professors\LearningSubgoal;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Professor;
use App\Models\Professors\Webinar;
use App\Models\Rating;
use App\Models\shop\Accent;
use App\Models\shop\Comment;
use App\Models\TeachingType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class GroupClassController extends Controller
{
    public function getFilters(Request $request)
    {
        $languages = Languages::select(['id', 'title'])->get();
        $teachingTypes = TeachingType::select(['id', 'title'])->get();
        $ageGroups = AgeGroup::select(['id', 'title'])->get();
        $languageLevels = LanguageLevel::select(['id', 'title'])->get();
        $genders = Gender::select(['id', 'title'])->get();
        $accent = Accent::select(['id', 'title'])->get();
        $direction = Direction::select(['id', 'title'])->get();
        $professor = Professor::get();
        $provinces = IranProvince::get(['id', 'name']);
        foreach ($provinces as &$province) {
            $province['value'] = $province->id;
        }
        $cities = IranCity::orderBy('name')->get(['id', 'name', 'province_id']);
        foreach ($cities as &$city) {
            $city['value'] = $city->id;
        }
//        $cities = IranCity::orderBy('')->get(['id', 'name', 'province_id']);
//        foreach ($cities as &$city) {
//            $city['value'] = $city->id;
//        }

        $minPrice = (int)$professor->min('price');
        $maxPrice = (int)$professor->max('price');

        $discountTypes = DiscountType::select('id', 'title')->get();
        foreach ($discountTypes as &$discountType) {
            $discountType['value'] = $discountType->id;
        }
        $experimentDiscountTypes = ExperimentDiscountType::select('id', 'title')->get();
        foreach ($experimentDiscountTypes as &$discountType) {
            $discountType['value'] = $discountType->id;
        }

        $result = [
            'teachingTypes' => $teachingTypes->map(fn($cat) => [
                'value' => $cat->id,
                'name' => $cat->title,
            ])->toArray(),
            'languageLevels' => $languageLevels->map(fn($publication) => [
                'value' => $publication->id,
                'name' => $publication->title,
            ])->toArray(),
            'age_group' => $ageGroups->map(fn($ageGroup) => [
                'value' => $ageGroup->id,
                'name' => $ageGroup->title,
            ])->toArray(),
            'genders' => $genders->map(fn($subject) => [
                'value' => $subject->id,
                'name' => $subject->title,
            ])->toArray(),
            'language' => $languages->map(fn($language) => [
                'value' => $language->id,
                'name' => $language->title,
            ])->toArray(),
            'accent' => $accent->map(fn($language) => [
                'value' => $language->id,
                'name' => $language->title,
            ])->toArray(),
            'direction' => $direction->map(fn($direction) => [
                'value' => $direction->id,
                'name' => $direction->title,
            ])->toArray(),
            'near' => [

                [
                    'key' => 1,
                    'title' => '30 دقیقه',
                ],
                [
                    'key' => 2,
                    'title' => '1 ساعت',
                ],
                [
                    'key' => 3,
                    'title' => '2 ساعت',
                ],
                [
                    'key' => 4,
                    'title' => '3 ساعت',
                ],
                [
                    'key' => 5,
                    'title' => '24 ساعت',
                ]

            ],
            'assessmentDiscountTypes' => $discountTypes->toArray(),
            'experimentDiscountTypes' => $experimentDiscountTypes->toArray(), // اضافه کردن تخفیف‌های کلاس‌های آزمایشی

            'price_range' => [
                'min_price' => 0,
                'max_price' => $maxPrice,
            ],
            'learning_goals' => LearningSubgoal::with(['goal'])
                ->get()
                ->groupBy(fn($learningGoal) => $learningGoal->goal->title ?? null)
                ->map(function ($learningGoalsGroup) {
                    $firstGoal = $learningGoalsGroup->first()->goal;

                    return [
                        'goal_id' => $firstGoal->id,
                        'goal_title' => $firstGoal->title,
                        'subgoals' => $learningGoalsGroup->map(fn($learningGoal) => [
                            'value' => $learningGoal->id,
                            'name' => $learningGoal->title,
                        ])->values(),
                    ];
                })
                ->values()
                ->toArray(),


        ];

        return api_response($result, 'فیلتر با موفقیت ارسال شد');
    }

    public function index(Request $request)
    {
        $group = GroupClass::with('professor', 'language.accents', 'level', 'discount', 'schedules')->where('admin_status', 'approved');


        if ($request->filled('is_inside')) {
            $isInside = $request->is_inside === 'in' ? 1 : ($request->is_inside === 'out' ? 0 : null);

            if (!is_null($isInside)) {
                $group->whereHas('professor', function ($q) use ($isInside) {
                    $q->where('is_inside', $isInside);
                });
            }
        }

        if ($request->filled('search')) {
            $group->whereRelation('subject', 'title', 'like', '%' . $request->query('search') . '%');
        }
        if ($request->filled('professor')) {
            $group->whereRelation('professor', 'first_name', 'like', '%' . $request->query('professor') . '%');
        }

        if ($request->filled('city')) {
            $group->whereRelation('address', 'city_id', '=', $request->city);
        }


        if ($request->filled('near')) {
            $nearMinutes = match ((int)$request->near) {
                1 => 30,
                2 => 60,
                3 => 120,
                4 => 180,
                5 => 1440,
                default => null,
            };

            if (!is_null($nearMinutes)) {
                $now = now();
                $to = now()->addMinutes($nearMinutes);
                $group->where('start_date')->whereHas('schedules', function ($q) use ($now, $to) {
                    $q->whereBetween('start_time', [$now, $to]);
                });
            }
        }


        if ($request->filled('country')) {
            $group->whereRelation('address', 'country_id', '=', $request->country);
        }


        if ($request->filled('professor')) {
            $group->whereRelation('professor', 'first_name', 'like', '%' . $request->query('professor') . '%');
        }
        if ($request->filled('language')) {
            $group->whereRelation('language', 'id', '=', $request->query('language'));
        }
        if ($request->filled('teachingTypes')) {
            $group->whereRelation('teachingType', 'id', '=', $request->teachingTypes);
        }
        if ($request->filled('accent')) {
            $group->whereRelation('language.accents', 'id', '=', $request->accent);
        }
        if ($request->filled('subject_id')) {
            $group->whereRelation('subject', 'id', '=', $request->subject_id);
        }
        if ($request->filled('languageLevels')) {
            $group->whereRelation('level', 'id', '=', $request->languageLevels);
        }

        if ($request->filled('age_group')) {
            $group->whereRelation('ageGroup', 'id', '=', $request->age_group);
        }
        if ($request->filled('genders')) {
            $group->whereRelation('gender', 'id', '=', $request->genders);
        }
        if ($request->filled('closest_time')) {
            $group->where('start_date', '>=', now())
                ->orderBy('start_date');
        }
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $group->whereBetween('new_total_price', [$request->min_price, $request->max_price]);
        } elseif ($request->filled('min_price')) {
            $group->where('new_total_price', '>=', $request->min_price);
        } elseif ($request->filled('max_price')) {
            $group->where('new_total_price', '<=', $request->max_price);
        }

        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'best_selling':
                    $group->orderBy('students_count', 'desc');
                    break;
                case 'newest':
                    $group->orderBy('created_at', 'desc');
                    break;
                case 'discounted':
                    $group->whereHas('discount', function ($q) {
                        $q->where('discount_percentage', '>', 0);
                    })
                        ->with('discount')
                        ->orderByDesc(
                            DiscountForGroupClass::select('discount_percentage')
                                ->whereColumn('discounts.group_classes_id', 'groupClass.id')
                                ->limit(1)
                        );
                    break;
                case 'most_viewed':
                    $group->orderBy('view', 'desc');
                    break;
                case 'cheap':
                    $group->orderBy('price', 'asc');
                    break;
                case 'expensive':
                    $group->orderBy('price', 'desc');
                    break;
            }
        }

        if ($request->filled('time_of_day')) {
            $timeOfDay = $request->query('time_of_day');
            $group->whereHas('schedules', function ($subQuery) use ($timeOfDay) {

                switch ($timeOfDay) {
                    case 'بامداد':
                        $subQuery->whereBetween('start_time', ['00:00:00', '5:59:59']);
                        break;
                    case 'صبح':
                        $subQuery->whereBetween('start_time', ['06:00:00', '11:59:59']);
                        break;
                    case 'بعد از ظهر':
                        $subQuery->whereBetween('start_time', ['12:00:00', '13:59:59']);
                        break;
                    case 'عصر':
                        $subQuery->whereBetween('start_time', ['14:00:00', '17:59:59']);
                        break;
                    case 'شب':
                        $subQuery->whereBetween('start_time', ['18:00:00', '23:59:59']);
                        break;

                }
            });
        }
        $groups = $group->latest()->paginate(12);

        $groups->getCollection()->transform(function ($class) {

            $isLiked = auth()->user() && $class->isLikedByUser(auth()->user()->id);
            $p = $class->discount->discount_percentage ?? null;
            $t = $class->professor->discount->last()?->discount_percentage ?? null;
            $firstSchedule = optional($class->schedules()->first());

            return [
                'id' => $class->id,
                'title' => $class->subject->title,
                'professor' => $class->professor->full_name(),
                'image' => $class->image ?? null,
                'language' => $class->language->title,
                'price' => $class->new_total_price,
                'discount' => $class->discount->discount_percentage ?? null,
                'price_discount' => $class->new_total_price - ($class->new_total_price * ($class->discount->discount_percentage ?? 0) / 100),
                'start_date' => $class->start_date ?? null,
                'end_date' => $class->end_date ?? null,
                'day' => $class->days . ' ' . $firstSchedule->start_time . '-' . $firstSchedule->end_time,
                'is_like' => Like::where('user_id', auth()->id())->where('likeable_id', $class->id)->where('likeable_type', GroupClass::class)->exists(),
                'view' => $class->view,
            ];
        });
        return api_response($groups, 'ok');
    }

    public function show($id)
    {


        $group = GroupClass::with('professor', 'language.accents', 'level')->findOrFail($id);
        $is_like = false;
        $rate = 0;
        if (auth()->user()) {
            $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $group->id)->where('likeable_type', GroupClass::class)->exists();
            $rate = Rating::where('user_id', auth()->user()->id)->where('ratable_id', $group->id)->where('ratable_type', GroupClass::class)->first();

            if ($rate) {
                $rating = $rate->rating; // نمره‌ای که کاربر داده است
            } else {
                $rating = 0; // اگر کاربر نمره‌ای نداده باشد، مقدار پیش‌فرض را تعیین کنید
            }
        }

        $group->increment('view');

        $book = GroupClassBook::where('group_class_id', '=', $id)->value('book_learning_id');
        $books = BookLearning::where('id', $book)->get();
        $related = GroupClass::where('subject_id', $group->subject_id)
            ->where('id', '!=', $id)
            ->with('professor', 'language.accents', 'level')
            ->take(4)->get()->map(function ($class) {
                $isLiked = auth()->user() && $class->isLikedByUser(auth()->user()->id);

                return [
                    'id' => $class->id,
                    'title' => $class->subject->title,
                    'professor' => $class->professor->full_name(),
                    'image' => $class->image ?? null,
                    'language' => $class->language->title,
                    'flag' => $class->language->icon,
                    'price' => $class->new_total_price,
                    'discount' => $class->total_percentage ?? null,
                    'price_discount' => $class->total_price,
                    'start_date' => optional($class->start_date),
                    'end_date' => optional($class->end_date),
                    'view' => $class->view,
                    'day' => $class->days . ' ' . optional($class->schedules()->first())->start_time . '-' . optional($class->schedules()->first())->end_time,
                    'is_like' => $isLiked,

                ];
            });

        $discount = $group->discount ?? null;
        if ($group->discount && $group->discount->end_date < now()) {
            if ($discount->discount_type === 'hourly') {
                $group->discount->delete();

            }
        }
        if ($group->discount) {
            if ($discount->discount_type === 'hourly') {
                if ($discount->start_date < now() || $discount->end_date > now()) {
                    $discount_type = 2;
                    $end_date = Carbon::parse($discount->end_date)->format('Y-m-d H:i:s');
                    $percentage = $discount->percentage;

                }
            } else if ($discount->discount_type === 'first_few') {
                $discount_type = 3;
                $count = $group->reserve()->where('payment_status', 'paid')->where('group_class_id', $id)->count();
                $all_count = $discount->first_few_count;
                $percentage = $discount->percentage;

            } else if ($discount->discount_type === 'special') {
                $discount_type = 4;
                $discount_for = $discount->specialDay->title;
                $date = $discount->specialDay->date;
                $percentage = $discount->percentage;
            } else if ($discount->discount_type === 'general') {
                $discount_type = 1;
                $percentage = $discount->percentage;
            }
        }
        $numberOfRatings = $group->ratings()->count();
        $averageRating = $group->ratings()->avg('rating');
        $averageRating = round($averageRating, 1);
        return api_response([
            'id' => $group->id,
            'title' => $group->subject->title,
            'professor' => $group->professor->full_name(),
            'professor_profile' => $group->professor->profile ? api::api . $group->professor->profile : null,
            'image' => $group->image ?? null,
            'language' => $group->language->title,
            'flag' => $group->language->icon,
            'teachingType' => $group->teachingType->title,
            'teaching_type_id' => $group->teaching_type_id,
            'level' => $group->level->title,
            'subject' => $group->subject->title,
            'gender' => $group->gender->title,
            'ageGroup' => $group->ageGroup->title,
            'professor_id' => $group->professor->id,
            'max_capacity' => $group->max_capacity,
            'sessions_count' => $group->sessions_count,
            'latitude' => $group->address->latitude ?? null,
            'longitude' => $group->address->longitude ?? null,
            'address_video' => $group->address->video ?? null,
            'address_city' => $group->address->city->name ?? null,
            'address' => $group->address ? $group->address->country->title . '_' . $group->address->city->name . '_' . $group->address->region : null,
            'schedules' => $group->days . ' ساعت ' . Carbon::parse($group->schedules->first()->end_time)->format('H:i') . '-' . Carbon::parse($group->schedules->first()->start_time)->format('H:i'),
            'مباحث دوره' => null,
            'platform' => $group->platform ?? null,
            'price' => $group->new_total_price,
            'discount' => $group->discount->discount_percentage ?? null,
            'price_discount' => $group->new_total_price - ($group->new_total_price * ($group->discount->discount_percentage ?? 0) / 100),
            'start_date' => Carbon::parse($group->start_date)->format('Y-m-d H:i:s'),
            'end_date' => Carbon::parse($group->end_date)->format('Y-m-d H:i:s'),
            'day' => $group->days . ' ' . $group->schedules()->first()->start_time . '-' . $group->schedules()->first()->end_time,
            'is_like' => $is_like,
            'view' => $group->view,
            'rate' => $rating ?? null,
            'rate_count' => $numberOfRatings ?? 0,
            'rate_av' => $averageRating ?? 0,
            'discount_type' => $discount_type ?? null,
            'discount_end_date' => $end_date ?? null,
            'discount_for' => $discount_for ?? null,
            'discount_count' => isset($count, $all_count) ? $all_count - $count : null,
            'discount_all_count' => $all_count ?? null,
            'date_discount' => $date ?? null,
            'percentage' => $percentage ?? null,
            'books' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language->title,
                'subject' => $book->subgoal->title,
                'age_group' => $book->ageGroup->title,
                'language_level' => $book->languageLevel->title,
                'images' => $book->product->images ?? null,
            ]),
            'related' => $related,
        ]);

    }

    public function calender(Request $request, $id)
    {
        $groupClass = GroupClass::findOrFail($id);
        $startDate = $request->input('start_day', $groupClass->start_date);

        $weekMap = [
            0 => 'یکشنبه',
            1 => 'دوشنبه',
            2 => 'سه‌شنبه',
            3 => 'چهارشنبه',
            4 => 'پنجشنبه',
            5 => 'جمعه',
            6 => 'شنبه',
        ];

        $orderedDays = ['شنبه', 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه'];

        $daysRange = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($startDate . " +$i days"));
            $dayName = $weekMap[date('w', strtotime($date))];
            $daysRange[$dayName] = $date;
        }

        $schedules = GroupClassSchedule::where('group_class_id', $id)->get();

        $formattedOutput = [];

        foreach ($schedules as $schedule) {
            $day = $schedule->day;

            if (isset($daysRange[$day])) {
                if (!isset($formattedOutput[$day])) {
                    $formattedOutput[$day] = [];
                }

                $formattedOutput[$day][] = [
                    'id' => $schedule->id,
                    'time' => substr($schedule->start_time, 0, 5) . ' - ' . substr($schedule->end_time, 0, 5),
                    'date' => $daysRange[$day],
                ];
            }
        }
        $sortedOutput = [];
        foreach ($orderedDays as $day) {
            if (isset($formattedOutput[$day])) {
                $sortedOutput[$day] = $formattedOutput[$day];
            }
        }

        return api_response($sortedOutput, 'ok');
    }

    public function toggleLike(Request $request, $id)
    {
        $group = GroupClass::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return api_response([], 'برای لایک کردن لاگین کنید', 401);
        }

        $like = $user->likes()
            ->where('likeable_id', $group->id)
            ->where('likeable_type', GroupClass::class)
            ->first();

        if ($like) {
            $like->delete();
            return api_response([], 'لایک حذف شد');
        }

        $group->likes()->create([
            'user_id' => $user->id,
        ]);

        return api_response([], 'لایک شد');
    }

    public function store_first_info(Request $request, $id)
    {
        $student = auth()->user()->student->id;
        $group = GroupClass::find($id);
        $request = $request->validate([
            'student_age' => 'required|integer',
            'student_message' => 'required|string',
            'discount_code' => 'nullable|string',
        ]);

        GroupClassReservation::create([
            'student_id' => $student,
            'group_class_id' => $id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'student_age' => $request['student_age'],
            'student_message' => $request['student_message'],
            'discount_code' => $request['discount_code'],
        ]);

        return api_response([], 'اطلاعات با موفقیت ثبت شد');
    }


    public function info22($reservationId)
    {
        $group = GroupClass::with('books', 'reserve')->find($reservationId);
        $book = GroupClassBook::where('group_class_id', '=', $reservationId)->value('book_learning_id');
        $books = BookLearning::where('id', $book)->get();

        $return = [
            'professor' => $group->professor->full_name(),
            'profile' => $group->professor->profile ?? null,
            'sessions_count' => $group->sessions_count,
            'max_capacity' => $group->max_capacity,
            'language' => $group->language->title,
            'flag' => $group->language->icon,
            'ageGroup' => $group->ageGroup->title,
            'id' => $group->id,
            'title' => $group->subject->title,
            'price' => $group->total_price,
            'offPrice' => $group->new_total_price,
            'discount' => $group->total_percentage,
            'level' => $group->level->title,
            'start_date' => $group->start_date,
            'end_date' => $group->end_date,
            'teachingType' => $group->teachingType->title,
            'place' => optional($group->address?->country)->title . '_' .
                optional($group->address?->city)->name . '_' .
                ($group->address->region ?? ''),
            'address' => $group->address?->address ?? '',
            'time' => $group->days . '-' . $group->schedules()->first()->start_time . '-' . $group->schedules()->first()->end_time,
            'group_class_id' => $reservationId,
            'books' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language->title,
                'subject' => $book->subgoal->title,
                'age_group' => $book->ageGroup->title,
                'language_level' => $book->languageLevel->title,
                'images' => $book->product->images ?? null,
            ]),
            'status' => 'pending',
            'payment_status' => 'pending',
            'discount_code' => $group->reserve()->first()->discount ?? null,
        ];
        return api_response($return, 'ok');

    }


    public function comment(Request $request, $groupClassId)
    {
        $request->validate([
            'body' => 'nullable|string|min:1|max:400', // حداقل طول 1 برای جلوگیری از رشته خالی
            'video_url' => 'nullable|file',
            'voice_url' => 'nullable|file',
        ], [
            'body' => 'توضیحات',
            'video_url' => 'ویدیو',
            'voice_url' => 'ویس',
        ]);


        $groupClass = GroupClass::findOrFail($groupClassId);

        $videoUrl = null;
        $voiceUrl = null;

        // ذخیره فایل ویدیو
        if ($request->hasFile('video_url')) {
            $videoPath = $request->file('video_url')->store('comment/videos', 'public');
            $videoUrl = $videoPath;
        }

        // ذخیره فایل صدا
        if ($request->hasFile('voice_url')) {
            $voicePath = $request->file('voice_url')->store('comment/voices', 'public');
            $voiceUrl = $voicePath;
        }

        // ساخت نظر
        $comment = $groupClass->comments()->create([
            'user_id' => auth()->user()->id,
            'body' => $request->body,
            'video_url' => $videoUrl,
            'voice_url' => $voiceUrl,
        ]);

        return api_response([
            'id' => $comment->id,
            'body' => $comment->body,
            'video_url' => $videoUrl ? asset($videoUrl) : null,
            'voice_url' => $voiceUrl ? asset($voiceUrl) : null,
            'created_at' => $comment->created_at->diffForHumans(),
        ], 'نظر با موفقیت ثبت شد');
    }

    public function showComments(Request $request, $groupClassId)
    {
        $user_id = auth()->id();

        $groupClass = GroupClass::findOrFail($groupClassId);


        $query = $groupClass->comments()->with([
            'user.student',
        ])->where('admin_status', 'approved');

        if ($user_id) {
            $query->with([
                'likes' => function ($q) use ($user_id) {
                    $q->where('user_id', $user_id);
                },
                'dislikes' => function ($q) use ($user_id) {
                    $q->where('user_id', $user_id);
                },
            ]);
        }

        if ($request->has('type')) {
            $type = $request->input('type');

            if ($type == 'text') {
                $query->whereNull('video_url')->whereNull('voice_url');
            } elseif ($type == 'video') {
                $query->whereNotNull('video_url')->whereNull('body');
            } elseif ($type == 'voice') {
                $query->whereNotNull('voice_url')->whereNull('body');
            }
        }

        $comments = $query->latest()->paginate();

        $comments->getCollection()->transform(function ($comment) use ($user_id) {
            return [
                'id' => $comment->id,
                'content' => $comment->body ?? null,
                'video_url' => $comment->video_url ? $comment->video_url : null,
                'voice_url' => $comment->voice_url ? $comment->voice_url : null,
                'created_at' => $comment->created_at->toDateTimeString(),
                'user' => [
                    'user_name' => optional($comment->user->student)->full_name() ?? null,
                    'image' => optional($comment->user->student)->profile
                        ? asset(api::api . $comment->user->student->profile)
                        : null,
                ],
                'likes_count' => $comment->likes->count(),
                'dislikes_count' => $comment->dislikes->count(),
                'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
            ];
        });

        return api_response($comments, 'ok');
    }

    public function rateClassGroup(Request $request, $groupClassId)
    {
        $user = auth()->user();

        if (!$user) {
            return api_response([], 'برای امتیازدهی باید لاگین کنید', 401);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ], [], [
            'rating' => 'امتیاز'
        ]);

        $classGroup = GroupClass::findOrFail($groupClassId);

        $existingRating = $user->ratings()
            ->where('ratable_id', $classGroup->id)
            ->where('ratable_type', GroupClass::class)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRating) {
            $existingRating->rating = $request->input('rating');
            $existingRating->save();
            return api_response([], 'امتیاز شما به‌روزرسانی شد');
        }

        $classGroup->ratings()->create([
            'user_id' => $user->id,
            'rating' => $request->input('rating'),
        ]);

        return api_response([], 'امتیاز شما با موفقیت ثبت شد');
    }

    public function pay_group_reservation(Request $request, $reservationId)
    {
        $request->validate([
            'code' => 'nullable|string',
        ]);


        $webinar = GroupClass::findOrFail($reservationId);
        $price = $webinar->new_total_price;
        if ($request->code) {
            $discount = DiscountForStudent::where('discountable_type', GroupClass::class)->where('code', $request->code)->first();
            if (!$discount) {
                return api_response([], 'تخفیفی پیدا نشد');
            }
            if ($discount->expires_at < now() or $discount->limit <= 0) {
                return api_response([], 'کد تخفیف منقضی شده ');
            }
            $percentage = $discount->percentage;
            $price = $price - ($price * $percentage / 100);
        }


        $reservation = GroupClassReservation::firstOrCreate(
            [
                'group_class_id' => $reservationId,
                'student_id' => auth()->user()->student->id,
                'payment_status' => 'pending',
                'discount_code' => $request->code ?? null,
                'final_price' => $price,
            ]
        );
        $group = $reservation->groupClass;

        $description = "پرداخت رزرو کلاس گروهی: " . $group->subject->title;

        $paymentService = new zarinpal();
        $callback = 'https://api.lingomasters.ir/zarin_callback';
        return $paymentService->pay($reservation, $price, $description, $callback);
    }


    public function zarin_callback(Request $request)
    {
        if (!$request->filled('Authority') || !$request->filled('Status'))
            return api_response([], 'خطا در پاسخ درگاه پرداخت', 403);

        $order_payment = Payment::where('authority', '=', $request->Authority)->first();


        if (blank($order_payment))
            return api_response([], 'اطلاعات پرداخت حذف شده است', 403);


        $price = $order_payment->amount;
        $authority = $order_payment->authority;
        $response = zarinpal()
            ->merchantId(config('zarinpal.merchant_id'))
            ->amount($price)
            ->verification()
            ->authority($authority)
            ->send();
        $redirect_url = 'https://lingomasters.ir/profile/wallet';

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $payable = $order_payment->payable;
            if ($payable instanceof GroupClassReservation) {
                $payable->update([
                    'payment_status' => 'failed',
                ]);
                $walletId = $payable->student->user->wallet->id;

                Transaction::create([
                    'type' => 'payment',
                    'amount' => $price,
                    'description' => "پرداخت موفق رزرو کلاس گروهی ",
                    'wallet_id' => $walletId,
                    'status' => 'approved',
                ]);

            }

            return redirect($redirect_url);
        }
        $ref = $response->referenceId();


        $payable = $order_payment->payable;
        if ($payable instanceof GroupClassReservation) {
            $payable->update([
                'payment_status' => 'paid',
            ]);
            $walletId = $payable->student->user->wallet->id;

            Transaction::create([
                'type' => 'payment',
                'amount' => $price,
                'description' => "پرداخت ناموفق رزرو کلاس خصوصی ",
                'wallet_id' => $walletId,
                'status' => 'rejected',
            ]);
        }

        $order_payment->update([
            'ref_id' => $ref,
            'status' => 'paid',
        ]);


        return redirect($redirect_url);
    }


    public function pay_private_reservation_Wallet(Request $request, $reservationId)
    {
        $wallet = auth()->user()->wallet;
        $request->validate([
            'code' => 'nullable|string',
        ]);
        $class = GroupClass::findOrFail($reservationId);
        $price = $class->new_total_price ?? 0;


        if ($request->code && $request->code != null) {
            $discount = DiscountForStudent::where('discountable_type', GroupClass::class)->where('code', $request->code)->first();
            if (!$discount) {
                return api_response([], 'تخفیفی پیدا نشد');
            }
            if ($discount->expires_at < now() or $discount->limit <= 0) {
                return api_response([], 'کد تخفیف منقضی شده ');
            }
            $percentage = $discount->percentage;
            $price = $price - ($price * $percentage / 100);
        }

        $reservation = GroupClassReservation::firstOrCreate(
            [
                'group_class_id' => $reservationId,
                'student_id' => auth()->user()->student->id,
                'payment_status' => 'pending',
                'status' => 'pending',
                'discount_code' => $request->code ?? null,
                'final_price' => $price,
            ]
        );

        if (!$reservation) {
            return api_response(null, '<UNK> <UNK> <UNK> <UNK>', 404);
        }
        $is_paid = GroupClassReservation::where('group_class_id', $reservationId)->where('student_id', auth()->user()->student->id)
            ->where('payment_status', 'paid')->exists();
        if ($is_paid) {
            return api_response([], 'شما قبلا این کلاس را رزرو کرده اید', 401);
        }

        $user_id = auth()->user()->id;

        $now = now()->format('His'); // ساعت به صورت 143251
        $rawString = $reservationId . '_' . $user_id . '_' . $now;

        $authority = crc32($rawString); // خروجی عدد صحیح

        $description = "پرداخت رزرو کلاس گروهی ";

        $pa = $reservation->payment()->create([
            'user_id' => auth()->id(),
            'gateway_id' => 2,
            'amount' => $price,
            'authority' => $authority,
            'status' => 'pending',
            'description' => $description,
        ]);
        $ta = Transaction::create([
            'type' => 'payment',
            'amount' => $price,
            'description' => $description,
            'wallet_id' => $wallet->id,
            'status' => 'Pending',
        ]);
        if ($wallet->balance > $price) {
            $wallet->balance -= $price;
            $wallet->save();
            $ta->update([
                'status' => 'approved',
            ]);
            $pa->update([
                'status' => 'paid',
            ]);
            $reservation->update([
                'payment_status' => 'paid',
                'status' => 'reserved',
            ]);
            return api_response([], 'پرداخت انجام شد و کلاس برای شما با موفقیت رزرو شد', 200);

        } else {
            $ta->update([
                'status' => 'rejected',
            ]);
            $pa->update([
                'status' => 'failed',
            ]);

            return api_response([], 'موجودی شما برای پرداخت این کلاس کافی نمیباشد', 400);
        }


    }


}
