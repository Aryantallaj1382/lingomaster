<?php

namespace App\Http\Controllers\Api\Professors;

use App\Models\contacts\api;
use App\Models\Professors\Notification;
use Illuminate\Routing\Controller;

// حتماً از این استفاده کن
use App\Models\AgeGroup;
use App\Models\Languages;
use App\Models\Professors\AgeGroupLanguageLevel;
use App\Models\Professors\BookLearning;
use App\Models\Professors\ClassPrice;
use App\Models\Professors\ClassSuggestion;
use App\Models\Professors\DiscountClassPrice;
use App\Models\Professors\DiscountForGroupClass;
use App\Models\Professors\GroupClass;
use App\Models\Professors\GroupClassBook;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningGoal;
use App\Models\Professors\LearningSubgoal;
use App\Models\Professors\Offline;
use App\Models\Professors\Online;
use App\Models\Professors\ProfessorLanguage;
use App\Models\Professors\ProfessorLearningGoal;
use App\Models\shop\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Reverb\Loggers\Log;


class GroupClassController extends Controller
{

    public function index(Request $request)
    {

        $id = auth()->user()->professor->id;
        $professor = auth()->user()->professor;
        $lang = $request->input('language');
        if (!$professor->canUseFeature("کلاس گروهی",$lang)) {
            return api_response([], 'شما مجاز به برگزاری وبینار نیستید. برای استفاده از این ویژگی، پلن VIP یا ویژه را تهیه کنید.');
        }
        $status = $request->input('status');
        $la = $request->input('language');

        $query = GroupClass::where('professors_id', $id)
            ->where('admin_status', 'approved')
            ->where('professor_status', $status)
            ->with(['subject', 'language', 'teachingType', 'schedules']) // اضافه کردن روابط
            ->where('language_id', $la)
            ->paginate(10);
        $query->getCollection()->transform(function ($groupClass) {
            $firstSchedule = $groupClass->schedules->first(); // دریافت اولین زمان‌بندی
            $schedules = 'تایمی تعیین نشده';

            if ($firstSchedule) {
                $startTime = \Carbon\Carbon::parse($firstSchedule->start_time)->format('H:i');
                $endTime = \Carbon\Carbon::parse($firstSchedule->end_time)->format('H:i');
                $schedules = 'روزهای' . ' ' . $groupClass->days . ' ' . $startTime . ' تا ' . $endTime;
            }

            $teachingTypeTitle = $groupClass->teachingType->title;
            $teachingTypeValue = $teachingTypeTitle === 'حضوری' ? 1 : 0;

            return [
                'id' => $groupClass->id,
                'title' => $groupClass->subject->title,
                'language' => $groupClass->language->title,
                'start_date' => $groupClass->start_date,
                'end_date' => $groupClass->end_date,
                'schedules' => $schedules, // مقدار زمان‌بندی
                'days' => $groupClass->days,
                'teaching_type' => $teachingTypeTitle,
                'teaching_type_value' => $teachingTypeValue, // مقدار جدید
                'image' => $groupClass->image,
                'platform' => $groupClass->platform,
                'max_capacity' => $groupClass->max_capacity,

            ];
        });

        return api_response($query, 'ok');
    }


    public function show($id)
    {
        $id2 = auth()->user()->professor->id;
        $class = GroupClass::where('professors_id', $id2)->with('schedules', 'books')->find($id);

        // Check if the class exists
        if (!$class) {
            return api_response(null, 'Class not found', 404); // Return an error response if not found
        }

        $book = GroupClassBook::where('group_class_id', '=', $id)->value('book_learning_id');
        $books = BookLearning::where('id', $book)->get();
        $statusMapping = [
            'pending' => 'در انتظار تایید ادمین',
            'approved' => 'تأیید ادمین',
            'rejected' => 'تایید نشده توسط ادمین',
            'filling' => 'در حال تکمیل ظرفیت',
            'in_progress' => 'انتقال به لیست کلاس ها',
            'not_filled' => 'عدم تکمیل ظرفیت',
        ];
        if ($class->is_professors == 1)
            $is_professor = 'استاد';
        else
            $is_professor = 'لینگومستر';
        $mappedClass = [
            'id' => $class->id,
            'language' => $class->language->title,
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
            'status' => $statusMapping[$class->admin_status] ?? $class->admin_status, // Translate status
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

    public function showDetails(Request $request)
    {
        $professor = auth()->user()->professor;

        $language_input = $request->input('language');

        if (!$language_input) {
            $firstLanguage = $professor->languages->first();
            if (!$firstLanguage) {
                return api_response([], 'No language found for this professor');
            }
            $language_input = $firstLanguage->id;
        }

        $data = [
            'learning_goals' => $professor->learningGoals()
                ->with(['subgoal.goal']) // بارگذاری subgoal و goal مربوطه
                ->get()
                ->groupBy(fn($learningGoal) => $learningGoal->subgoal?->goal?->title ?? null) // گروه‌بندی بر اساس goal_id
                ->map(function ($learningGoalsGroup) {
                    $firstGoal = $learningGoalsGroup->first()?->subgoal?->goal;
                    return [
                        'goal_id' => $firstGoal?->id ?? null,
                        'goal_title' => $firstGoal?->title ?? null,
                        'subgoals' => $learningGoalsGroup->map(fn($learningGoal) => [
                            'id' => $learningGoal->subgoal?->id ?? null,
                            'title' => $learningGoal->subgoal?->title ?? null,
                        ])->unique('id')->values(),
                    ];
                })
                ->values()
                ->toArray(),

            'language_levels' => $professor->ageGroupLanguageLevels()
                ->where('language_id', $language_input)
                ->with('languageLevel')
                ->get()
                ->map(function ($level) {
                    return [
                        'id' => $level->languageLevel->id,
                        'title' => $level->languageLevel->title,
                    ];
                })
                ->unique('id')
                ->values(),

            'gender' => $professor->genders()
                ->wherePivot('language_id', $language_input)
                ->get()
                ->map(function ($gender) {
                    return [
                        'id' => $gender->id,
                        'title' => $gender->title,
                    ];
                }),

            'ageGroups' => $professor->ageGroups()
                ->wherePivot('language_id', $language_input) // یا ->where('language_id', $language_input)
                ->get()
                ->map(function ($ageGroup) {
                    return [
                        'id' => $ageGroup->id,
                        'title' => $ageGroup->title,
                    ];
                }),

            'platforms' => Online::where('professor_id', $professor->id)->get()->map(function ($platform) {
                return [
                    'id' => $platform->id,
                    'title' => $platform->platform_name,
                    'language' => $platform->language_id
                ];
            }),
            'addresses' => Offline::where('professor_id', $professor->id)->get()->map(function ($ageGroups) {
                return [
                    'id' => $ageGroups->id,
                    'title' => $ageGroups->address,
                    'language' => $ageGroups->language_id

                ];
            }),
        ];
        return api_response($data);
    }

    public function getPendingGroupClass(Request $request)
    {
        // Get the authenticated professor's ID
        $professorId = auth()->user()->professor->id;


        $groupClass = GroupClass::where('professors_id', $professorId)
            ->where('professor_status', 'pending')
            ->with('books', 'schedules', 'discount')
            ->first();
        if (!$groupClass) {
            return api_response(null, 'کلاس پندینگ وجود ندارد', 200);
        }

        $base_price = ClassPrice::where('professor_id', $professorId)->where('language_id', $groupClass->language_id)->value('base_price');
        if ($groupClass) {
            $days = $groupClass->schedules->pluck('day')->unique()->values()->toArray();

            $times = $groupClass->schedules
                ->map(fn($schedule) => \Carbon\Carbon::parse($schedule->start_time)->format('H:i') .
                    ' - ' .
                    \Carbon\Carbon::parse($schedule->end_time)->format('H:i'))
                ->unique()
                ->values()
                ->toArray();

            $discount = DiscountClassPrice::where('professor_id', $groupClass->professors_id)
                ->where('class_mode', 'group')
                ->value('discount_percentage');

            $classPrice = ClassPrice::where('professor_id', $groupClass->professors_id)
                ->where('language_id', $groupClass->language_id)
                ->where('class_type', 'group') // چون کلاس گروهی است
                ->first();

            $base_price = ClassPrice::where('professor_id', $groupClass->professors_id)
                ->where('language_id', $groupClass->language_id)
                ->where('class_type', 'group') // چون کلاس گروهی است
                ->value('base_price');


            if (!$classPrice) {
                return api_response(null, 'شهریه ای برای کلاس گروهی ثبت نشده است', 404);
            }

            $priceForLevel = match ($groupClass->level_id) {
                1 => $classPrice->beginner_price,
                2 => $classPrice->intermediate_price,
                3 => $classPrice->advanced_price,
                default => null,
            };

            // قیمت بر اساس گروه سنی
            $priceForAge = match ($groupClass->age_group_id) {
                1 => $classPrice->child_price,
                2 => $classPrice->teen_price,
                3 => $classPrice->adult_price,
                default => null,
            };

            // قیمت بر اساس محل برگزاری
            $priceForPlace = match ($groupClass->teaching_type_id) {
                2 => $classPrice->online_price ?? 0,
                1 => $classPrice->intermediate_price ?? 0,
                default => 0,
            };

            $total = $priceForAge + $priceForLevel + $priceForPlace;
            $total_price = $total + $base_price;


            $responseData = [
                'age_group' => (string)($groupClass->age_group_id) ?? null,
                'books' => $groupClass->books->pluck('book_learning_id')->toArray(),
                'class_link' => $groupClass->class_link ?? null,
                'day' => $groupClass->days ?? null,
                'discount' => $groupClass->discount,
                'gender' => (string)($groupClass->gender_id) ?? null,
                'hour' => (string)($groupClass->hourly) ?? null,
                'id' => $groupClass->id,
                'language' => (string)($groupClass->language_id) ?? null,
                'level_id' => (string)($groupClass->level_id) ?? null,
                'max_capacity' => $groupClass->max_capacity ?? null,
                'min_capacity' => $groupClass->min_capacity ?? null,
                'platform' => (string)(Online::where('professor_id', $professorId)
                    ->when(!empty($groupClass->platform), function ($query) use ($groupClass) {
                        return $query->where('platform_name', 'like', '%' . $groupClass->platform . '%');
                    })
                    ->first()?->id ?? null),

                'price_increase' => $groupClass->price_increase ?? 0,
                'times' => $times,
                'days' => $days,
                'start_date' => $groupClass->start_date ? $groupClass->start_date : null,
                'end_date' => $groupClass->end_date ? $groupClass->end_date : null,
                'sessions_count' => (string)($groupClass->sessions_count) ?? null,
                'subject_id' => (string)($groupClass->subject_id) ?? null,
                'teaching_type' => (string)($groupClass->teaching_type_id) ?? null,
                'address' => (string)($groupClass->address_id) ?? null,
                'total_price' => $total_price,
                'percentage' => $discount,
            ];

            // Return the formatted response
            return api_response($responseData, 'کلاس پندینگ پیدا شد');
        }

        // Return a response if no pending class was found
        return api_response(null, 'کلاس پندینگ وجود ندارد');
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'informations.teaching_type' => 'required|exists:teaching_types,id',
            'informations.gender' => 'required|exists:genders,id',
            'informations.age_group' => 'required|exists:age_groups,id',
            'informations.level_id' => 'required|exists:language_levels,id',
            'informations.subject_id' => 'required',
            'informations.language' => 'required|exists:languages,id',
            'informations.min_capacity' => 'required|integer|min:1',
            'informations.max_capacity' => 'required|integer|min:1',
            'informations.sessions_count' => 'required|integer|min:1',
            'informations.platform' => 'nullable|string',
            'informations.hour' => 'nullable',
            'informations.day' => 'nullable',
            'informations.address' => 'nullable',
            'books' => 'sometimes|array',
            'books.*' => 'exists:book_learning,id',
            'name' => 'nullable|string',
        ], [], [
            'informations.teaching_type' => 'نوع تدریس',
            'informations.gender' => 'جنسیت',
            'informations.age_group' => 'گروه سنی',
            'informations.level_id' => 'سطح زبان',
            'informations.subject_id' => 'موضوع',
            'informations.language' => 'زبان',
            'informations.min_capacity' => 'حداقل ظرفیت',
            'informations.max_capacity' => 'حداکثر ظرفیت',
            'informations.sessions_count' => 'تعداد جلسات',
            'informations.platform' => 'پلتفرم',
            'informations.hour' => 'ساعت',
            'informations.day' => 'روز',
            'informations.address' => 'آدرس',
            'books' => 'کتاب‌ها',
            'books.*' => 'کتاب انتخاب‌شده',
            'name' => 'نام کلاس',
        ]);

        $data['books'] = $data['books'] ?? [];
        $data['professors_id'] = auth()->user()->professor->id;
        $professors_id = auth()->user()->professor->id;
        $data['professor_status'] = 'pending';

        $data1 = [
            'teaching_type_id' => $data['informations']['teaching_type'],
            'gender_id' => $data['informations']['gender'],
            'age_group_id' => $data['informations']['age_group'],
            'level_id' => $data['informations']['level_id'],
            'subject_id' => $data['informations']['subject_id'],
            'language_id' => $data['informations']['language'],
            'min_capacity' => $data['informations']['min_capacity'],
            'max_capacity' => $data['informations']['max_capacity'],
            'sessions_count' => $data['informations']['sessions_count'],
            'platform' => $data['informations']['platform'] ?? null,
            'address_id' => $data['informations']['address'] ?? null,
            'days' => $data['informations']['day'],
            'hourly' => $data['informations']['hour'],
            'professors_id' => $data['professors_id'],
            'professor_status' => $data['professor_status'],
        ];
        $platform = Online::where('professor_id', $professors_id)->where('id', optional($data['informations'])['platform'])->get('platform_name');
        $data1['platform'] = $platform[0]->platform_name ?? null;


        if ($groupClass = GroupClass::where('professors_id', $data1['professors_id'])->where('professor_status', 'pending')->first()) {
            if ((int)$groupClass->hourly !== (int)$data1['hourly']) {
                $groupClass->schedules()->delete();
            }

            $groupClass->update($data1);
        } else {
            $groupClass = GroupClass::create($data1);
        }


        GroupClassBook::where('group_class_id', $groupClass->id)->delete();

        if (!empty($data['books'])) {
            foreach ($data['books'] as $bookId) {
                GroupClassBook::create([
                    'group_class_id' => $groupClass->id,
                    'book_learning_id' => $bookId,
                ]);
            }
        } elseif (!empty($data['name'])) {
            GroupClassBook::create([
                'group_class_id' => $groupClass->id,
                'book_learning_id' => null, // مقدار `null` چون کتاب از `name` می‌آید
                'name' => $data['name'], // فیلدی برای ذخیره نام کتاب
            ]);
        }


        return api_response(['id' => $groupClass->id], 'اطلاعات اولیه کلاس با موفقیت ثبت شد');
    }

    public function storeGroupClassSchedule(Request $request, GroupClass $groupClass)
    {
        $validatedData = $request->validate([
            'days' => 'required|array',
            'times' => 'required|array',
            'times.*' => ['regex:/^\d{2}:\d{2} - \d{2}:\d{2}$/'],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [], [
            'days' => 'روزها',
            'times' => 'ساعات',
            'times.*' => 'فرمت ساعت',
            'start_date' => 'تاریخ شروع',
            'end_date' => 'تاریخ پایان',
        ]);


        $schedules = [];

        foreach ($validatedData['days'] as $day) {
            foreach ($validatedData['times'] as $time) {
                [$start_time, $end_time] = explode(' - ', $time);

                $scheduleData = [
                    'day' => $day,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                ];

                $groupClass->schedules()->create($scheduleData);
            }
        }

        $groupClass->update([
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
        ]);

        return api_response([], 'ساعت ها ثبت شد');
    }

    public function price(Request $request, $group_classes_id)
    {
        $request->validate([
            'discount_type' => 'nullable|in:general,special,first_few,hourly',
            'discount_percentage' => 'nullable|integer|min:1|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'first_few_count' => 'nullable|integer|min:1',
            'special_day_id' => 'nullable|exists:special_days,id',
            'description' => 'nullable|string',
            'price_increase' => 'nullable|numeric|min:0',
            'total_price' => 'nullable|numeric|min:0',
            'new_total_price' => 'nullable|numeric|min:0',
            'profile_discount' => 'nullable|min:0|max:100',
            'percentage' => 'nullable|min:0|max:100',
        ], [], [
            'discount_type' => 'نوع تخفیف',
            'discount_percentage' => 'درصد تخفیف',
            'start_date' => 'تاریخ شروع',
            'end_date' => 'تاریخ پایان',
            'first_few_count' => 'تعداد نفرات اول',
            'special_day_id' => 'روز خاص',
            'description' => 'توضیحات',
            'price_increase' => 'افزایش قیمت',
            'profile_discount' => 'تخفیف پروفایل',
        ]);


        $discountData = $request->only([
            'discount_type', 'discount_percentage', 'start_date', 'end_date', 'first_few_count', 'special_day_id', 'description'
        ]);
        $percentage = $request->percentage ?? 0;
        $discount_percentage = $request->discount_percentage ?? 0;
        $total_discount = $percentage + $discount_percentage;
        $discountData = array_filter($discountData, fn($value) => !is_null($value));

        DiscountForGroupClass::updateOrCreate(
            ['group_classes_id' => $group_classes_id],
            $discountData
        );

        $priceData = $request->only(['price_increase', 'profile_discount','new_total_price','total_price']);

        $priceData['total_percentage'] = $total_discount;

        if (!empty(array_filter($priceData, fn($value) => !is_null($value)))) {
            GroupClass::where('id', $group_classes_id)->update($priceData);
        }

        return api_response([], 'قیمت‌ها ثبت شدند');
    }


    public function show_info($id)
    {
        $groupClass = GroupClass::with(['books', 'discount', 'schedules', 'professor', 'language'])
            ->find($id);

        if (!$groupClass) {
            return api_response(null, 'کلاس یافت نشد', 404);
        }

        // پیدا کردن قیمت مناسب از جدول class_prices
        $classPrice = ClassPrice::where('professor_id', $groupClass->professors_id)
            ->where('language_id', $groupClass->language_id)
            ->where('class_type', 'group') // چون کلاس گروهی است
            ->first();
        $Price = ClassPrice::where('professor_id', $groupClass->professors_id)
            ->where('language_id', $groupClass->language_id)
            ->where('class_type', 'group') // چون کلاس گروهی است
            ->value('base_price');

        if (!$classPrice) {
            return api_response(null, 'قیمت کلاس یافت نشد', 404);
        }

        // قیمت بر اساس سطح
        $priceForLevel = match ($groupClass->level_id) {
            1 => $classPrice->beginner_price,
            2 => $classPrice->intermediate_price,
            3 => $classPrice->advanced_price,
            default => null,
        };

        // قیمت بر اساس گروه سنی
        $priceForAge = match ($groupClass->age_group_id) {
            1 => $classPrice->child_price,
            2 => $classPrice->teen_price,
            3 => $classPrice->adult_price,
            default => null,
        };

        // قیمت بر اساس محل برگزاری
        $priceForPlace = match ($groupClass->teaching_type_id) {
            2 => $classPrice->online_price ?? null,
            1 => $classPrice->intermediate_price
        ,
            default => null,
        };
        if ($groupClass->profile_discount == 1) {
            $discount = DiscountClassPrice::where('professor_id', $groupClass->professors_id)
                ->where('class_mode', 'group')
                ->value('discount_percentage');

        }
        $basePrice = max([$priceForAge, $priceForLevel, $priceForPlace]);

        $totalDiscount = ($groupClass->discount->discount_percentage ?? 0) + ($discount ?? 0);

        $totalPrice = $basePrice ? (($basePrice + $Price) * (1 - $totalDiscount / 100)) : null;
        $book = GroupClassBook::where('group_class_id', '=', $id)->value('book_learning_id');
        $books = BookLearning::where('id', $book)->get();

        return api_response([

            'id' => $groupClass->id,
            'language' => $groupClass->language->title,
            'subject' => $groupClass->subject->goal->title . ' (' . $groupClass->subject->title . ')',
            'start_date' => $groupClass->start_date,
            'end_date' => $groupClass->end_date,
            'schedules' => \Carbon\Carbon::parse($groupClass->schedules->first()->end_time)->format('H:i') . '-' . \Carbon\Carbon::parse($groupClass->schedules->first()->start_time)->format('H:i'),
            'days' => $groupClass->days,
            'teaching_type' => $groupClass->teachingType->title,
            'teaching_type_id' => $groupClass->teaching_type_id,
            'max_capacity' => $groupClass->max_capacity,
            'min_capacity' => $groupClass->min_capacity,
            'sessions_count' => $groupClass->sessions_count,
            'ageGroup' => $groupClass->ageGroup->title,
            'gender' => $groupClass->gender->title,
            'level' => $groupClass->level->title,
            'platform' => $groupClass->platform,
            'profile_discount' => $groupClass->profile_discount,
            'hourly' => $groupClass->hourly,
            'books' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language->title,
                'subject' => $book->subgoal->title,
                'age_group' => $book->ageGroup->title,
                'language_level' => $book->languageLevel->title,
                'images' => $book->product && $book->product->images
                    ? api::api . $book->product->images()->first()->path
                    : null,
            ]),
            'class_link' => $groupClass->class_link,
            'is_professors' => $groupClass->is_professors,
            'admin_status' => $groupClass->admin_status,
            'image' => $groupClass->image,
            'address' => $groupClass->address->address ?? null,
            'discount_type' => $groupClass->discount->discount_type ?? null,
            'discount_percentage' => $groupClass->total_percentage,
            'total_price' => $groupClass->new_total_price,
            'price' =>$groupClass->total_price,

        ], 'اطلاعات کلاس دریافت شد');
    }


    public function getProfessorBooks(Request $request)
    {
        $sub = $request->input('subgoal_id');
        $professorId = auth()->user()->professor->id;

        $books = BookLearning::where('professor_id', $professorId)->where('subgoal_id', $sub)->where('status', 'approved')->get()->map(function ($BookLearning) {
            return [
                'id' => $BookLearning->id,
                'title' => $BookLearning->title,
                'language_id' => $BookLearning->language->title,
                'subject' => $BookLearning->subgoal->title,
                'age_group' => $BookLearning->ageGroup->title,
                'language_level' => $BookLearning->languageLevel->title,
                'images' => $BookLearning->product && $BookLearning->product->images()->first() ? 'https://api.lingomasters.ir/' . $BookLearning->product->images()->first()->path : 'https://api.lingomasters.ir/images/22.png',];
        });

        return api_response($books, 'ok');
    }

    public function confirmGroupClass(Request $request, $group_class_id)
    {
        $groupClass = GroupClass::findOrFail($group_class_id);

        $groupClass->update([
            'professor_status' => 'reserved',
            'admin_status' => 'pending',
        ]);
        Notification::create([
            'user_id' => $groupClass->professor->user->id,
            'text' =>  'برای شما ثبت شد بعد از تایید ادمین به لیست کلاس های شما اضافه میشود'.$groupClass->subject.'گلاس گرهی  ',
        ]);
        return api_response([], 'کلاس گروهی با موفقیت تایید شد و بعد از تایید ادمین در لیست کلاس ها نمایش داده میشود');
    }
    public function rejectGroupClass(Request $request, $group_class_id)
    {
        try {
            $groupClass = GroupClass::findOrFail($group_class_id);
            $groupClass->delete();
            return api_response([], ' کلاس با موفقیت حذف شد');
        } catch (\Exception $e) {

            return api_response(null, 'خطا در حذف کلاس', 500);
        }
    }


    public function generateClassSuggestions()
    {
        $id = auth()->user()->professor->id;
        // دریافت پیشنهادات اخیر (کمتر از ۲ روز)
        $recentSuggestions = ClassSuggestion::where('professor_id', $id)
            ->where('created_at', '>=', Carbon::now()->subDays(2))
            ->get();

        // اگر پیشنهادات اخیر موجود باشند، همان‌ها را ارسال کنیم
        if ($recentSuggestions->isNotEmpty()) {
            return api_response($recentSuggestions, 'پیشنهادات اخیر بازگردانده شد.');
        }

        // حذف پیشنهادات قدیمی
        ClassSuggestion::where('professor_id', $id)->delete();


        $suggestions = [];

        $subgoalIds = ProfessorLearningGoal::where('professor_id', $id)->pluck('subgoal_id');
        $subGoalTitles = LearningSubgoal::whereIn('id', $subgoalIds)->pluck('title')->toArray();

        $languagesid = ProfessorLanguage::where('professor_id', $id)->pluck('language_id');
        $languages = Languages::whereIn('id', $languagesid)->pluck('title')->toArray();

        $ageid = AgeGroupLanguageLevel::where('professor_id', $id)->pluck('age_group_id');
        $age = AgeGroup::whereIn('id', $ageid)->pluck('title')->toArray();

        $levelid = AgeGroupLanguageLevel::where('professor_id', $id)->pluck('language_level_id');
        $level = LanguageLevel::whereIn('id', $levelid)->pluck('title')->toArray();

        $days = ['روز های زوج', 'روز های فرد', 'آخر هفته ها'];

        $products = Product::whereIn('subject_id', $subgoalIds)
            ->whereIn('language_levels_id', $levelid)
            ->whereIn('language_id', $languagesid)
            ->get();
        for ($i = 0; $i < 6; $i++) {
            $randomProduct = $products->isNotEmpty() ? $products->random() : null;

            $startDate = now()->addDays(rand(30, 60));
            $endDate = $startDate->copy()->addDays(rand(30, 120));
            $startHour = rand(8, 18);
            $endHour = min($startHour + rand(1, 2), 21);
            if (
                empty($subGoalTitles) ||
                empty($languages) ||
                empty($level) ||
                empty($age)
            ) {
                return api_response([], 'برای ایجاد پیشنهاد کلاس، ابتدا باید اطلاعاتی مانند زبان، هدف، سطح زبان و گروه سنی ثبت کنید.', 422);
            }

            $randomDay = $days[array_rand($days)];

            $suggestionData = [
                'professor_id' => $id,
                'title' => $subGoalTitles[array_rand($subGoalTitles)],
                'language' => $languages[array_rand($languages)],
                'days' => $days[array_rand($days)],
                'level' => $level[array_rand($level)],
                'age' => $age[array_rand($age)],
                'start_date' => $startDate->toDateString(),
                'hourly' => ($endHour - $startHour),
                'end_date' => $endDate->toDateString(),
                'schedules' => sprintf('%s %02d تا %02d', $randomDay, $startHour, $endHour),
                'max_capacity' => rand(20, 50),
                'sessions_count' => rand(10, 30),
                'teaching_type_value' => 1,
                'image' => null,

            ];

            // ذخیره در دیتابیس
            ClassSuggestion::create($suggestionData);

            // ذخیره در آرایه خروجی
            $suggestions[] = $suggestionData;
        }


        return api_response($suggestions, 'ok');
    }


    public function getStudentsInClass($id)
    {
        $idP = auth()->user()->professor->id;

        $reservations = GroupClassReservation::with('student:id,first_name,last_name,profile')
            ->where('group_class_id', $id)
            ->whereHas('groupClass', function ($query) use ($idP) {
                $query->where('professors_id', $idP);
            })
            ->where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->paginate();

        // اصلاح داده‌های دانش‌آموزان
        $reservations->getCollection()->transform(function ($reservation) {
            return [
                'id' => $reservation->student->id,
                'name' => $reservation->student->first_name . ' ' . $reservation->student->last_name,
                'profile' => $reservation->student->profile ?? 'https://api.lingomasters.ir/images/22.png',
            ];
        });

        return api_response($reservations, 'لیست دانش‌آموزان کلاس با موفقیت بارگذاری شد');
    }

    public function updateClassLink(Request $request, $id)
    {
        $request->validate([
            'class_link' => 'required',
        ]);

        $class = GroupClass::find($id);

        if (!$class) {
            return api_response(null, 'کلاس مورد نظر یافت نشد', 404);
        }

        $class->update([
            'class_link' => $request->class_link,
        ]);

        return api_response($class, 'لینک کلاس با موفقیت بروزرسانی شد');
    }


    public function index2(Request $request)
    {
        $id = auth()->user()->professor->id;
        $la = $request->input('language');
        $query = GroupClass::where('professors_id', $id)
            ->where('professor_status', 'reserved')
            ->where('admin_status', '!=', 'approved')
            ->where('language_id', $la)
            ->latest()
            ->get();

        $classesByProfessor = [];
        $classesNotByProfessor = [];
        $statusMappingColor = [
            'pending' => 'warning',
            'rejected' => 'danger',
            'filling' => 'success',
            'not_filled' => 'danger',
        ];

        $statusMapping = [
            'pending' => 'در انتظار تایید ادمین',
            'approved' => 'تأیید ادمین',
            'rejected' => 'تایید نشده توسط ادمین',
            'filling' => 'در حال تکمیل ظرفیت',
            'in_progress' => 'انتقال به لیست کلاس ها',
            'not_filled' => 'عدم تکمیل ظرفیت',
        ];

        foreach ($query as $groupClass) {
            $firstSchedule = $groupClass->schedules()->first();

            if ($firstSchedule) {
                $startTime = \Carbon\Carbon::parse($firstSchedule->start_time)->format('H:i');
                $endTime = \Carbon\Carbon::parse($firstSchedule->end_time)->format('H:i');

                $schedules = $groupClass->days . ' ' . $startTime . ' تا ' . $endTime;
            } else {
                $schedules = '';
            }

            $teachingTypeTitle = $groupClass->teachingType->title;
            $teachingTypeValue = $teachingTypeTitle === 'حضوری' ? 1 : 0;
            $mappedClass = [
                'id' => $groupClass->id,
                'title' => $groupClass->subject->title,
                'language' => $groupClass->language->title,
                'start_date' => $groupClass->start_date,
                'end_date' => $groupClass->end_date,
                'schedules' => $schedules,
                'max_capacity' => $groupClass->max_capacity,
                'days' => $groupClass->days,
                'status' => $statusMapping[$groupClass->admin_status] ?? $groupClass->admin_status,
                'statusColor' => $statusMappingColor[$groupClass->admin_status] ?? $groupClass->admin_status,
                'teaching_type' => $teachingTypeTitle,
                'teaching_type_value' => $teachingTypeValue,
                'platform' => $groupClass->platform,
                'image' => $groupClass->image,
            ];

            if ($groupClass->is_professors == 1) {
                $classesByProfessor[] = $mappedClass;
            } else {
                $classesNotByProfessor[] = $mappedClass;
            }
        }

        return api_response([
            'professor_classes' => $classesByProfessor,
            'lingomasters_classes' => $classesNotByProfessor
        ], 'ok');
    }


    public function professor_classes(Request $request)
    {
        $id = auth()->user()->professor->id;
        $la = $request->input('language');

        $query = GroupClass::where('professors_id', $id)
            ->where('professor_status', 'reserved')
            ->where('admin_status', '!=', 'approved')
            ->where('is_professors', '=', 1)
            ->where('language_id', $la)
            ->paginate();

        $statusMappingColor = [
            'pending' => 'warning',
            'rejected' => 'danger',
            'filling' => 'success',
            'not_filled' => 'danger',
        ];

        $statusMapping = [
            'pending' => 'در انتظار تایید ادمین',
            'approved' => 'تأیید ادمین',
            'rejected' => 'تایید نشده توسط ادمین',
            'filling' => 'در حال تکمیل ظرفیت',
            'in_progress' => 'انتقال به لیست کلاس ها',
            'not_filled' => 'عدم تکمیل ظرفیت',
        ];
        $classesByProfessor = $query->getCollection()->transform(function ($groupClass) use ($statusMapping, $statusMappingColor) {
            $firstSchedule = $groupClass->schedules()->first();

            if ($firstSchedule) {
                $startTime = \Carbon\Carbon::parse($firstSchedule->start_time)->format('H:i');
                $endTime = \Carbon\Carbon::parse($firstSchedule->end_time)->format('H:i');
                $schedules = $groupClass->days . ' ' . $startTime . ' تا ' . $endTime;
            } else {
                $schedules = '';
            }

            $teachingTypeTitle = $groupClass->teachingType->title;
            $teachingTypeValue = $teachingTypeTitle === 'حضوری' ? 1 : 0;

            return [
                'id' => $groupClass->id,
                'title' => $groupClass->subject->title,
                'language' => $groupClass->language->title,
                'start_date' => $groupClass->start_date,
                'end_date' => $groupClass->end_date,
                'schedules' => $schedules,
                'max_capacity' => $groupClass->max_capacity,
                'days' => $groupClass->days,
                'status' => $statusMapping[$groupClass->admin_status] ?? $groupClass->admin_status,
                'statusColor' => $statusMappingColor[$groupClass->admin_status] ?? $groupClass->admin_status,
                'teaching_type' => $teachingTypeTitle,
                'teaching_type_value' => $teachingTypeValue,
                'platform' => $groupClass->platform,
                'image' => $groupClass->image,
            ];
        });

        $query->setCollection($classesByProfessor);

        return api_response($query, 'ok'); // کل شیء پیجینیشن را برگردانید
    }

    public function lingo_class(Request $request)
    {
        $id = auth()->user()->professor->id;
        $la = $request->input('language');

        $query = GroupClass::where('professors_id', $id)
            ->where('professor_status', 'reserved')
            ->where('admin_status', '!=', 'approved')
            ->where('is_professors', '=', 0)
            ->where('language_id', $la)
            ->paginate();

        $statusMappingColor = [
            'pending' => 'warning',
            'rejected' => 'danger',
            'filling' => 'success',
            'not_filled' => 'danger',
        ];

        $statusMapping = [
            'pending' => 'در انتظار تایید ادمین',
            'approved' => 'تأیید ادمین',
            'rejected' => 'تایید نشده توسط ادمین',
            'filling' => 'در حال تکمیل ظرفیت',
            'in_progress' => 'انتقال به لیست کلاس ها',
            'not_filled' => 'عدم تکمیل ظرفیت',
        ];

        $classesByProfessor = $query->getCollection()->transform(function ($groupClass) use ($statusMapping, $statusMappingColor) {
            $firstSchedule = $groupClass->schedules()->first();

            if ($firstSchedule) {
                $startTime = \Carbon\Carbon::parse($firstSchedule->start_time)->format('H:i');
                $endTime = \Carbon\Carbon::parse($firstSchedule->end_time)->format('H:i');
                $schedules = $groupClass->days . ' ' . $startTime . ' تا ' . $endTime;
            } else {
                $schedules = '';
            }

            $teachingTypeTitle = $groupClass->teachingType->title;
            $teachingTypeValue = $teachingTypeTitle === 'حضوری' ? 1 : 0;

            return [
                'id' => $groupClass->id,
                'title' => $groupClass->subject->title,
                'language' => $groupClass->language->title,
                'start_date' => $groupClass->start_date,
                'end_date' => $groupClass->end_date,
                'schedules' => $schedules,
                'max_capacity' => $groupClass->max_capacity,
                'days' => $groupClass->days,
                'status' => $statusMapping[$groupClass->admin_status] ?? $groupClass->admin_status,
                'statusColor' => $statusMappingColor[$groupClass->admin_status] ?? $groupClass->admin_status,
                'teaching_type' => $teachingTypeTitle,
                'teaching_type_value' => $teachingTypeValue,
                'platform' => $groupClass->platform,
                'image' => $groupClass->image,
            ];
        });

        $query->setCollection($classesByProfessor);

        return api_response($query, 'ok'); // کل شیء پیجینیشن را برگردانید
    }


    public function getClassSuggestionDetails($id)
    {

        $suggestion = ClassSuggestion::where('professor_id', auth()->user()->professor->id)->find($id);
        $id_f = auth()->user()->professor->id;
        if (!$suggestion) {
            return api_response(null, 'پیشنهاد مورد نظر یافت نشد', 404);
        }
        $lang = Languages::where('title', $suggestion->language)->value('id');
        $levle = LanguageLevel::where('title', $suggestion->level)->value('id');
        $age = AgeGroup::where('title', $suggestion->age)->value('id');
        $teaching_type = request()->input('teaching_type');


        $books = BookLearning::where('language_id', $lang)->where('language_level_id', $levle)->get();

        $discount = DiscountClassPrice::where('professor_id', $id_f)
            ->where('class_mode', 'group')
            ->value('discount_percentage');

        $classPrice = ClassPrice::where('professor_id', $id_f)
            ->where('language_id', $lang)
            ->where('class_type', 'group') // چون کلاس گروهی است
            ->first();

        $base_price = ClassPrice::where('professor_id', $id_f)
            ->where('language_id', $lang)
            ->where('class_type', 'group') // چون کلاس گروهی است
            ->value('base_price');


        $priceForLevel = match ($levle) {
            1 => $classPrice->beginner_price,
            2 => $classPrice->intermediate_price,
            3 => $classPrice->advanced_price,
            default => null,
        };

        // قیمت بر اساس گروه سنی
        $priceForAge = match ($age) {
            1 => $classPrice->child_price,
            2 => $classPrice->teen_price,
            3 => $classPrice->adult_price,
            default => null,
        };

        // قیمت بر اساس محل برگزاری
        $priceForPlace = match ($teaching_type) {
            2 => $classPrice->online_price ?? null,
            1 => $classPrice->intermediate_price
        ,
            default => $classPrice->intermediate_price
        ,
        };
        $total = $priceForAge + $priceForLevel + $priceForPlace;
        $total_price = $total + $base_price;


        $sub = LearningSubgoal::where('title', $suggestion->title)->value('goal_id');
        $goal = LearningGoal::where('id', $sub)->value('title');
        $response = [
            'id' => $suggestion->id,
            'subject' => $goal . ' (' . $suggestion->title . ')',
            'language' => $suggestion->language ?? null,
            'level' => $suggestion->level ?? null,
            'ageGroup' => $suggestion->age ?? null,
            'start_date' => $suggestion->start_date,
            'end_date' => $suggestion->end_date,
            'schedules' => $suggestion->schedules,
            'sessions_count' => $suggestion->sessions_count,
            'teaching_type' => $teaching_type ?? null,
            'image' => $suggestion->image ?? null,
            'days' => $suggestion->days ?? null,
            'hourly' => $suggestion->hourly ?? null,
            'price2' => $total_price,
            'price_increase' => 0,
            'percentage' => $discount,
            'khalozhni_baset' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language->title,
                'subject' => $book->subgoal->title,
                'age_group' => $book->ageGroup->title,
                'language_level' => $book->languageLevel->title,
                'images' => $book->product->images ?? null,
            ]),
            'platforms' => Online::where('professor_id', $id_f)->where('language_id', $lang)->get()->map(function ($platform) {
                return [
                    'id' => $platform->id,
                    'title' => $platform->platform_name
                ];
            }),

            'addresses' => Offline::where('professor_id', $id_f)->where('language_id', $lang)->get()->map(function ($ageGroups) {
                return [
                    'id' => $ageGroups->id,
                    'title' => $ageGroups->title,
                    'address' => $ageGroups->address,
                ];
            }),

        ];

        return api_response($response, 'جزئیات پیشنهاد با موفقیت دریافت شد');
    }


    public function SuggestionsStore(Request $request)
    {
        $data = $request->validate([
            'min_capacity' => 'required|integer|min:1',
            'max_capacity' => 'required|integer|min:1',
            'platform' => 'nullable',
            'hourly' => 'nullable',
            'days' => 'required|string',
            'teaching_type_id' => 'required',
            'books' => 'sometimes|array',
            'books.*' => 'exists:book_learning,id',
            'total_price' => 'nullable',
            'id' => 'required',
            'address_id' => 'nullable',
            'price_increase' => 'nullable',
            'name' => 'nullable',
            'schedules' => 'nullable',
        ], [], [
            'min_capacity' => 'حداقل ظرفیت',
            'max_capacity' => 'حداکثر ظرفیت',
            'platform' => 'پلتفرم',
            'hourly' => 'ساعتی بودن',
            'days' => 'روزها',
            'teaching_type_id' => 'نوع تدریس',
            'books' => 'کتاب‌ها',
            'books.*' => 'کتاب انتخابی',
            'total_price' => 'قیمت کل',
            'id' => 'شناسه',
            'address_id' => 'آدرس',
            'price_increase' => 'افزایش قیمت',
            'name' => 'نام',
            'schedules' => 'برنامه زمانی',
        ]);


        $id = auth()->user()->professor->id;
        $suggestion = ClassSuggestion::find($data['id']);

        $lang = Languages::where('title', $suggestion->language)->value('id');
        $levle = LanguageLevel::where('title', $suggestion->level)->value('id');
        $age = AgeGroup::where('title', $suggestion->age)->value('id');
        $goal = LearningSubgoal::where('title', $suggestion->title)->value('id');

        preg_match('/(\d{1,2})\s*تا\s*(\d{1,2})/', $data['schedules'], $matches);

        if (!$matches) {
            return response()->json(['error' => 'Invalid days format'], 400);
        }

        $start_time = sprintf('%02d:00', $matches[1]);
        $end_time = sprintf('%02d:00', $matches[2]);

        $daysArray = [$data['days']];

        $groupClass = GroupClass::create([
            'professors_id' => $id,
            'age_group_id' => $age,
            'level_id' => $levle,
            'subject_id' => $goal,
            'language_id' => $lang,
            'sessions_count' => $suggestion->sessions_count,
            'teaching_type_id' => $data['teaching_type_id'],
            'address_id' => $data['address_id'] ?? null,
            'gender_id' => null,
            'min_capacity' => $data['min_capacity'],
            'max_capacity' => $data['max_capacity'],
            'hourly' => $data['hourly'],
            'platform' => $data['platform'] ?? null,
            'days' => $data['days'], // مقدار اصلی `days` ذخیره می‌شود
            'start_date' => $suggestion->start_date,
            'end_date' => $suggestion->end_date,
            'is_professors' => 0,
            'total_price' => $data['total_price'],
            'profile_discount' => 0,
            'price_increase' => $data['price_increase'],
            'professor_status' => 'reserved',
            'admin_status' => 'pending',
        ]);

        // ذخیره اطلاعات در جدول `schedules`
        foreach ($daysArray as $day) {
            $groupClass->schedules()->create([
                'day' => $day,
                'start_time' => $start_time,
                'end_time' => $end_time,
            ]);
        }


        if (!empty($data['books'])) {
            foreach ($data['books'] as $bookId) {
                GroupClassBook::create([
                    'group_class_id' => $groupClass->id,
                    'book_learning_id' => $bookId,
                ]);
            }
        } elseif (!empty($data['name'])) {
            GroupClassBook::create([
                'group_class_id' => $groupClass->id,
                'book_learning_id' => null, // مقدار `null` چون کتاب از `name` می‌آید
                'name' => $data['name'], // فیلدی برای ذخیره نام کتاب
            ]);
        }


        return api_response([], 'اطلاعات اولیه کلاس با موفقیت ثبت شد');
    }

}
