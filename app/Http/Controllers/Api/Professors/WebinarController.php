<?php

namespace App\Http\Controllers\Api\Professors;

use App\Models\contacts\api;
use App\Models\Professors\Gender;
use App\Models\Professors\GroupClass;
use App\Models\Professors\LearningGoal;
use App\Models\Professors\Notification;
use App\Models\Professors\ProfessorGender;
use App\Models\Professors\WebinarReservation;
use Illuminate\Routing\Controller;
use App\Models\AgeGroup;
use App\Models\Languages;
use App\Models\Professors\AgeGroupLanguageLevel;
use App\Models\Professors\BookLearning;
use App\Models\Professors\ClassPrice;
use App\Models\Professors\ClassSuggestion;
use App\Models\Professors\DiscountClassPrice;
use App\Models\Professors\DiscountForWebinars;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningSubgoal;
use App\Models\Professors\Online;
use App\Models\Professors\ProfessorLanguage;
use App\Models\Professors\ProfessorLearningGoal;
use App\Models\Professors\Webinar;
use App\Models\Professors\webinarsBook;
use App\Models\Professors\WebinarSuggestion;
use App\Models\shop\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebinarController extends Controller
{

    public function index(Request $request)
    {
        $professor = auth()->user()->professor;
        $lang = $request->input('language');

        if (!$professor->canUseFeature("وبینار",$lang)) {
            return api_response([], 'شما مجاز به برگزاری وبینار نیستید. برای استفاده از این ویژگی، پلن VIP یا ویژه را تهیه کنید.' , 400);
        }
        $id_professor = auth()->user()->professor->id;
        Webinar::where('admin_status', 'pending')
            ->where('created_at', '<', Carbon::now()->subMinutes(2))
            ->delete();

        $status = $request->query('status');
        $webinar = Webinar::where('professors_id', $id_professor)->where('professor_status', $status)->where('admin_status', 'approved')->get();
        $webinars = $webinar->map(function ($webinar) {
            return [
                'id' => $webinar->id,
                'title' => 'وبینار' . ' ' . $webinar->language->title . ' ' . $webinar->subject->title . ' ' . 'سطح' . ' ' . $webinar->level->title,
                'date' => $webinar->date,
                'time' => $webinar->hour,
                'max_capacity' => $webinar->max_capacity,
                'platform' => $webinar->platform,
                'language' => $webinar->language->title,

            ];
        });
        return api_response(
            $webinars,
            'لیست وبینارها با موفقیت بارگذاری شد',
            200,

        );
    }
    public function generateWebinarsSuggestions()
    {
        $id = auth()->user()->professor->id;
        $recentSuggestions = WebinarSuggestion::where('professor_id', $id)
            ->where('created_at', '>=', Carbon::now()->subDays(2))
            ->get();

        if ($recentSuggestions->isNotEmpty()) {
            return api_response($recentSuggestions);
        }
        WebinarSuggestion::where('professor_id', $id)->delete();


        $suggestions = [];

        $subgoalIds = ProfessorLearningGoal::where('professor_id', $id)->pluck('subgoal_id');
        $subGoalTitles = LearningSubgoal::whereIn('id', $subgoalIds)->pluck('title')->toArray();

        $languagesid = ProfessorLanguage::where('professor_id', $id)->pluck('language_id');
        $languages = Languages::whereIn('id', $languagesid)->pluck('title')->toArray();

        $ageid = AgeGroupLanguageLevel::where('professor_id', $id)->pluck('age_group_id');
        $age = AgeGroup::whereIn('id', $ageid)->pluck('title')->toArray();

        $levelid = AgeGroupLanguageLevel::where('professor_id', $id)->pluck('language_level_id');
        $level = LanguageLevel::whereIn('id', $levelid)->pluck('title')->toArray();


        $products = Product::whereIn('subject_id', $subgoalIds)
            ->whereIn('language_levels_id', $levelid)
            ->whereIn('language_id', $languagesid)
            ->get();
        if (
            empty($subGoalTitles) ||
            empty($languages) ||
            empty($level) ||
            empty($age)
        ) {
            return api_response([], 'برای ایجاد پیشنهاد کلاس، ابتدا باید اطلاعاتی مانند زبان، هدف، سطح زبان و گروه سنی ثبت کنید.', 422);
        }
        for ($i = 0; $i < 6; $i++) {
            $randomProduct = $products->isNotEmpty() ? $products->random() : null;

            $startDate = now()->addDays(rand(30, 60));
            $endDate = $startDate->copy()->addDays(rand(30, 120));

            $startHour = rand(8, 18);
            $endHour = min($startHour + rand(1, 2), 21);


            $suggestionData = [
                'professor_id' => $id,
                'title' => $subGoalTitles[array_rand($subGoalTitles)],
                'language' => $languages[array_rand($languages)],
                'level' => $level[array_rand($level)],
                'age' => $age[array_rand($age)],
                'date' => $startDate->toDateString(),
                'max_capacity' => rand(20, 50),
                'image' => null,
                'time' => $startHour . ':' . '00',

            ];

            WebinarSuggestion::create($suggestionData);

            $suggestions[] = $suggestionData;
        }


        return api_response($suggestions);
    }


    public function show($id)
    {
        $id_professor = auth()->user()->professor->id;
        $webinar = Webinar::where('professors_id', $id_professor)->find($id);

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

        ];

        return api_response($webinar);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'informations.gender' => 'required|exists:genders,id',
            'informations.age_group' => 'required|exists:age_groups,id',
            'informations.level_id' => 'required|exists:language_levels,id',
            'informations.subject_id' => 'required',
            'informations.teaching_type_id' => 'nullable|exists:teaching_types,id',
            'informations.language' => 'required|exists:languages,id',
            'informations.min_capacity' => 'required|integer|min:1',
            'informations.max_capacity' => 'required|integer|min:1',
            'informations.sessions_count' => 'nullable|integer|min:1',
            'informations.platform' => 'nullable|string',
            'informations.time' => 'required',
            'informations.date' => 'required',
            'informations.headline' => 'nullable|array',
            'informations.headline.*' => 'nullable|string', // هر آیتم باید رشته باشه            'books' => 'sometimes|array',
            'books.*' => 'exists:book_learning,id',
            'name' => 'nullable|string',

        ], [], [
                [
                    'informations.gender' => 'جنسیت',
                    'informations.age_group' => 'گروه سنی',
                    'informations.level_id' => 'سطح زبان',
                    'informations.subject_id' => 'موضوع',
                    'informations.teaching_type_id' => 'نوع تدریس',
                    'informations.language' => 'زبان',
                    'informations.min_capacity' => 'حداقل ظرفیت',
                    'informations.max_capacity' => 'حداکثر ظرفیت',
                    'informations.sessions_count' => 'تعداد جلسات',
                    'informations.platform' => 'پلتفرم',
                    'informations.time' => 'زمان',
                    'informations.date' => 'تاریخ',
                    'informations.headline' => 'عنوان',
                    'books' => 'کتاب‌ها',
                    'books.*' => 'کتاب',
                    'name' => 'نام',
                ],
            ]

        );
        $id_p = auth()->user()->professor->id;
        $data1 = [
            'gender_id' => $data['informations']['gender'], // تغییر فیلد gender به gender_id
            'age_group_id' => $data['informations']['age_group'],
            'level_id' => $data['informations']['level_id'],
            'subject_id' => $data['informations']['subject_id'],
            'language_id' => $data['informations']['language'],
            'min_capacity' => $data['informations']['min_capacity'],
            'date' => $data['informations']['date'],
            'hour' => $data['informations']['time'] ?? null,
            'max_capacity' => $data['informations']['max_capacity'],
            'platform' => $data['informations']['platform'] ?? null,
            'headline' => $data['informations']['headline'] ?? null,
            'professors_id' => $id_p,
        ];


        $data['books'] = $data['books'] ?? [];
        $data1['professors_id'] = auth()->user()->professor->id;
        $professors_id = auth()->user()->professor->id;
        $data1['professor_status'] = 'pending';
        $platform = Online::where('professor_id', $professors_id)->where('id', optional($data1)['platform'])->get('platform_name');
        $data1['platform'] = $platform[0]->platform_name ?? null;

        $webinar = Webinar::create($data1);
        webinarsBook::where('webinar_id', $webinar->id)->delete();

        if (!empty($data['books'])) {
            foreach ($data['books'] as $bookId) {
                webinarsBook::create([
                    'webinar_id' => $webinar->id,
                    'book_learning_id' => $bookId,
                ]);
            }
        } elseif (!empty($data['name'])) {
            webinarsBook::create([
                'webinar_id' => $webinar->id,
                'book_learning_id' => null,
                'name' => $data['name'],
            ]);
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
            1 => $classPrice->beginner_price?? 0,
            2 => $classPrice->intermediate_price?? 0,
            3 => $classPrice->advanced_price?? 0,
            default => null,
        };
        $priceForAge = match ($webinar->level->id) {
            1 => $classPrice->child_price?? 0,
            2 => $classPrice->teen_price?? 0,
            3 => $classPrice->adult_price?? 0,
            default => null,
        };
        $discount = DiscountClassPrice::where('professor_id', $webinar->professors_id)
            ->where('class_mode', 'webinar')
            ->value('discount_percentage') ?? 0;


        $total_price = $priceForAge + $priceForLevel + $Price;
        $percentage = $discount ?? 0;

        $r = [
            'id' => $webinar->id,
            'total_price' => $total_price,
            'percentage' => $percentage,
            'price_increase' => 0,

        ];


        return api_response($r, 'وبینار با موفقیت ایجاد شد', 201);
    }


    public function price(Request $request, $webinar)
    {
        $data =  $request->validate([
            'discount_type' => 'nullable|in:general,special,first_few,hourly',
            'discount_percentage' => 'nullable|integer|min:1|max:100',
            'percentage' => 'nullable|integer|min:1|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'first_few_count' => 'nullable|integer|min:1',
            'special_day_id' => 'nullable|exists:special_days,id',
            'price_increase' => 'nullable|numeric|min:0',
            'profile_discount' => 'nullable',
            'new_total_price'=> 'nullable|numeric',
            'total_price'=> 'nullable|numeric',
        ]);


        $percentage = $request->percentage ?? 0;
        $discount_percentage = $request->discount_percentage ?? 0;
        $total_discount = $percentage + $discount_percentage;

        $discountData = $request->only([
            'discount_type', 'discount_percentage', 'start_date', 'end_date', 'first_few_count', 'special_day_id'
        ]);

        $discountData = array_filter($discountData, fn($value) => !is_null($value));

        DiscountForWebinars::updateOrCreate(
            ['webinars_id' => $webinar],
            $discountData
        );
        $data1 = [
            'price_increase' => $data['price_increase'], // تغییر فیلد gender به gender_id
            'profile_discount' => $data['profile_discount']?? 0,
            'new_total_price' => $data['new_total_price'],
            'total_price' => $data['total_price'],

        ];


        $data1['total_percentage'] = $total_discount;


        if (!empty(array_filter($data1, fn($value) => !is_null($value)))) {
            Webinar::where('id', $webinar)->update($data1);
        }

        return api_response([], 'قیمت‌ها ثبت شدند');
    }


    public function rejectWebinar(Request $request, $webinar)
    {
        try {
            $groupClass = Webinar::findOrFail($webinar);
            $groupClass->delete();
            return api_response([], ' کلاس با موفقیت حذف شد');
        } catch (\Exception $e) {

            return api_response(null, 'خطا در حذف کلاس', 500);
        }
    }

    public function confirm_webinar(Request $request, $webinar)
    {
        $groupClass = Webinar::findOrFail($webinar);

        $groupClass->update([
            'professor_status' => 'reserved',
            'admin_status' => 'pending',
        ]);

        Notification::create([
            'user_id' => $groupClass->professor->user->id,
            'text' => 'برای شما ثبت شد بعد از تایید ادمین به لیست کلاس های شما اضافه میشود' . $groupClass->subject->title . 'وبینار ',
        ]);
        return api_response([], 'وبینار با موفقیت تایید شد و بعد از تایید ادمین در لیست کلاس ها نمایش داده میشود');
    }


    public function getClassSuggestionDetails($id)
    {
        $id_f = auth()->user()->professor->id;

        $suggestion = WebinarSuggestion::where('professor_id', $id_f)->find($id);
        if (!$suggestion) {
            return api_response(null, 'پیشنهاد مورد نظر یافت نشد', 404);
        }
        $lang = Languages::where('title', $suggestion->language)->value('id');
        $levle = LanguageLevel::where('title', $suggestion->level)->value('id');
        $age = AgeGroup::where('title', $suggestion->age)->value('id');


        $books = BookLearning::where('language_id', $lang)->where('language_level_id', $levle)->get();


        $discount = DiscountClassPrice::where('professor_id', $id_f)
            ->where('class_mode', 'webinar')
            ->value('discount_percentage');

        $classPrice = ClassPrice::where('professor_id', $id_f)
            ->where('language_id', $lang)
            ->where('class_type', 'webinar') // چون کلاس گروهی است
            ->first();

        $base_price = ClassPrice::where('professor_id', $id_f)
            ->where('language_id', $lang)
            ->where('class_type', 'webinar') // چون کلاس گروهی است
            ->value('base_price');


        $priceForLevel = match ($levle) {
            1 => $classPrice->beginner_price?? 0,
            2 => $classPrice->intermediate_price?? 0,
            3 => $classPrice->advanced_price?? 0,
            default => null,
        };

        // قیمت بر اساس گروه سنی
        $priceForAge = match ($age) {
            1 => $classPrice->child_price?? 0,
            2 => $classPrice->teen_price?? 0,
            3 => $classPrice->adult_price?? 0,
            default => null,
        };


        $total = $priceForAge + $priceForLevel;
        $total_price = $total + $base_price;

//        $gender= ProfessorGender::where('professor_id', $id)->pluck('gender_id');
//        $genderTitles = Gender::whereIn('id', $gender)->pluck('title')->toArray();

        $sub = LearningSubgoal::where('title', $suggestion->title)->value('goal_id');
        $goal = LearningGoal::where('id', $sub)->value('title');

        $response = [
            'id' => $suggestion->id,
            'subject' => $goal . ' (' . $suggestion->title . ')',
            'language' => $suggestion->language ?? null,
            'level' => $suggestion->level ?? null,
            'ageGroup' => $suggestion->age ?? null,
            'date' => $suggestion->date,
            'time' => $suggestion->time,
            'image' => $suggestion->image ?? null,
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
        ];

        return api_response($response, 'جزئیات پیشنهاد با موفقیت دریافت شد');
    }
    public function updateClassLink(Request $request, $id)
    {
        $request->validate([
            'class_link' => 'required',
        ]);

        $class = Webinar::find($id);

        if (!$class) {
            return api_response(null, 'کلاس مورد نظر یافت نشد', 404);
        }

        $class->update([
            'class_link' => $request->class_link,
        ]);

        return api_response($class, 'لینک کلاس با موفقیت بروزرسانی شد');
    }
    public function SuggestionsStore(Request $request)
    {
        $data = $request->validate([
            'min_capacity' => 'required|integer|min:1',
            'max_capacity' => 'required|integer|min:1',
            'platform' => 'nullable',
            'books' => 'sometimes|array',
            'books.*' => 'exists:book_learning,id',
            'total_price' => 'nullable',
            'id' => 'required',
            'price_increase' => 'nullable',
            'title' => 'nullable',
            'book_name' => 'nullable',
            'gender' => 'nullable',
        ], [], [
            'min_capacity' => 'حداقل ظرفیت',
            'max_capacity' => 'حداکثر ظرفیت',
            'platform' => 'پلتفرم',
            'hour' => 'ساعتی بودن',
            'books' => 'کتاب‌ها',
            'books.*' => 'کتاب انتخابی',
            'total_price' => 'قیمت کل',
            'price_increase' => 'افزایش قیمت',
            'name' => 'نام',
            'title' => 'سرفصل',
        ]);


        $id = auth()->user()->professor->id;
        $suggestion = WebinarSuggestion::find($data['id']);

        $lang = Languages::where('title', $suggestion->language)->value('id');

        $levle = LanguageLevel::where('title', $suggestion->level)->value('id');

        $age = AgeGroup::where('title', $suggestion->age)->value('id');

        $goal = LearningSubgoal::where('title', $suggestion->title)->value('id');


        $groupClass = Webinar::create([
            'professors_id' => $id,
            'age_group_id' => $age,
            'gender_id' => null,
            'level_id' => $levle,
            'subject_id' => $goal,
            'language_id' => $lang,
            'min_capacity' => $data['min_capacity'],
            'max_capacity' => $data['max_capacity'],
            'platform' => $data['platform'] ?? null,
            'date' => $suggestion->date,
            'hour' => $suggestion->time,
            'is_professors' => 0,
            'total_price' => $data['total_price'],
            'profile_discount' => 0,
            'price_increase' => $data['price_increase'],
            'professor_status' => 'reserved',
            'admin_status' => 'pending',
        ]);


        if (!empty($data['books'])) {
            foreach ($data['books'] as $bookId) {
                webinarsBook::create([
                    'webinar_id' => $groupClass->id,
                    'book_learning_id' => $bookId,
                ]);
            }
        } elseif (!empty($data['name'])) {
            webinarsBook::create([
                'webinar_id' => $groupClass->id,
                'book_learning_id' => null, // مقدار `null` چون کتاب از `name` می‌آید
                'name' => $data['name'], // فیلدی برای ذخیره نام کتاب
            ]);
        }


        return api_response([], 'اطلاعات اولیه کلاس با موفقیت ثبت شد');
    }

    public function getStudentsInClass($id)
    {
        $idP = auth()->user()->professor->id;

        $reservations = WebinarReservation::with('student:id,first_name,last_name,profile')
            ->where('webinar_id', $id)
            ->whereHas('webinar', function ($query) use ($idP) {
                $query->where('professors_id', $idP);
            })
            ->where('status', 'confirmed')
            ->where('payment_status', 'paid')
            ->paginate();

        $reservations->getCollection()->transform(function ($reservation) {
            return [
                'id' => $reservation->student->id,
                'name' => $reservation->student->first_name . ' ' . $reservation->student->last_name,
                'profile' => $reservation->student->profile ? api::api . $reservation->student->profile : 'https://api.lingomasters.ir/images/22.png',];
        });

        return api_response($reservations, 'لیست دانش‌آموزان کلاس با موفقیت بارگذاری شد');
    }

}
