<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\iran_regions\IranCity;
use App\Models\iran_regions\IranProvince;
use App\Models\Languages;
use App\Models\Professors\AssessmentType;
use App\Models\Professors\DiscountType;
use App\Models\Professors\ExperimentDiscountType;
use App\Models\Professors\Gender;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\Professor;
use App\Models\shop\Accent;
use App\Models\TeachingType;
use Illuminate\Http\Request;

class PrivetClassController extends Controller
{
    public function index(Request $request)
    {
        $query = Professor::with([
            'teachingTypes',
            'genders',
            'ageGroups',
            'languageLevels',
            'languages',
            'teacherTimeSlots',
            'accent',
            'profile',
            'cover',
            'about_video',
        ]);

        if ($request->filled('time_of_day')) {
            $timeOfDay = $request->query('time_of_day');
            $query->whereHas('teacherTimeSlots', function ($subQuery) use ($timeOfDay) {
//                $subQuery->where('date', '>=', now());

                switch ($timeOfDay) {
                    case 'بامداد':
                        $subQuery->whereBetween('time', ['00:00:00', '5:59:59']);
                        break;
                    case 'صبح':
                        $subQuery->whereBetween('time', ['06:00:00', '11:59:59']);
                        break;
                    case 'بعد از ظهر':
                        $subQuery->whereBetween('time', ['12:00:00', '13:59:59']);
                        break;
                    case 'عصر':
                        $subQuery->whereBetween('time', ['14:00:00', '17:59:59']);
                        break;
                    case 'شب':
                        $subQuery->whereBetween('time', ['18:00:00', '23:59:59']);
                        break;

                }
            });
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->query('search') . '%');
        }
        if ($request->filled('language')) {
            $query->whereHas('languages', function ($subQuery) use ($request) {
                $subQuery->where('languages.id', '=', $request->language); // نام جدول مشخص شده است
            });
        }

        if ($request->filled('teachingTypes')) {
            $query->whereRelation('teachingTypes', 'id', '=', $request->teachingTypes);
        }
        if ($request->filled('accent')) {
            $query->whereRelation('accent', 'id', '=', $request->accent);
        }
        if ($request->filled('languageLevels')) {
            $query->whereRelation('languageLevels', 'id', '=', $request->languageLevels);
        }
        if ($request->filled('examLanguages')) {
            $query->whereRelation('examLanguages', 'id', '=', $request->examLanguages);
        }
        if ($request->filled('examLanguages')) {
            $query->whereRelation('examLanguages', 'id', '=', $request->examLanguages);
        }
        if ($request->filled('testLanguages')) {
            $query->whereRelation('ageGroups', 'id', '=', $request->age_group);
        }
        if ($request->filled('genders')) {
            $query->whereRelation('genders', 'id', '=', $request->genders);
        }
        if ($request->filled('is_inside')) {
            $isInside = $request->is_inside === 'in' ? 1 : ($request->is_inside === 'out' ? 0 : null);
            if (!is_null($isInside)) {
                $query->where('is_inside', $isInside);
            }
        }

        if ($request->filled('closest_time')) {
            $query->whereHas('teacherTimeSlots', function ($subQuery) {
                $subQuery->where('date', '>=', now())
                    ->orderBy('date', 'asc')
                    ->orderBy('time', 'asc');
            });
        }
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereBetween('price', [$request->min_price, $request->max_price]);
        } elseif ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        } elseif ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }


        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'best_selling':
                    $query->orderBy('students_count', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'discounted':
                    $query->where('discount', '>', 0)->orderBy('discount', 'desc');
                    break;
                case 'most_viewed':
                    $query->orderBy('views_count', 'desc');
                    break;
                case 'cheap':
                    $query->orderBy('price', 'asc');
                    break;
                case 'expensive':
                    $query->orderBy('price', 'desc');
                    break;
            }
        }


        $professors = $query->paginate($request->per_page ?? 10);
        $professors->getCollection()->transform(function ($professor) {
            return [
                'name' => $professor->name,
                'teaching_types' => $professor->teachingTypes->pluck('title')->toArray(),
                'age_groups' => $professor->ageGroups->pluck('title')->toArray(),
                'language_levels' => $professor->languageLevels->pluck('title')->toArray(),
                'genders' => $professor->genders->pluck('title')->toArray(),
                'languages' => $professor->languages->pluck('flag')->toArray(),
                'group_classes_count' => $professor->group_classes_count,
                'private_classes_count' => $professor->private_classes_count,
                'students_count' => $professor->students_count,
                'closest_time' => optional($professor->teacherTimeSlots->first())->date . ' ' . optional($professor->teacherTimeSlots->first())->time,
                'country' => 'ایران',
                'city' => 'تهران',
                'region' => 'صادقیه',
                'about_video' => $professor->about_video?->first()?->path ? api::api  . $professor->about_video->first()->path : null,
                'about_text' => $professor->about_text,
                'cover' => $professor->cover?->first()?->path ? api::api  . $professor->cover->first()->path : null,
                'profile' => $professor->profile?->first()?->path ? api::api  . $professor->profile->first()->path : null,
                'id' => $professor->id,
                'price' => $professor->price,
                'attendance' => $professor->attendance,
                'views_count' => $professor->views_count,
                'is_liked' => false,
                'rate' => 3,
                'rate_count' => 6,
            ];
        });

        return api_response($professors);
    }

    public function show(Professor $professor)
    {
        $professor->increment('views_count');

        $languages = [];

        foreach ($professor->languages as $language) {
            // دریافت تمام subgoal‌های استاد برای این زبان
            $professorSubgoals = $professor->learningGoals->where('language_id', $language->id);

            $languageData = [];

            foreach ($professorSubgoals as $learningGoal) {
                $subgoal = $learningGoal->subgoal;
                $goal = $subgoal->goal;

                if (!isset($languageData[$goal->title])) {
                    $languageData[$goal->title] = [];
                }

                $languageData[$goal->title][$subgoal->sub][] = $subgoal->title;
            }

            $languages[$language->title] = $languageData;
        }
        return api_response([
            'about' => [
                'views_count' => $professor->views_count,
                'name' => $professor->first_name,
                'gender' => $professor->genders->pluck('title')->toArray(), // اطلاعات جنسیت
                'teaching_types' => $professor->teachingTypes->pluck('title')->toArray(), // نوع تدریس
                'age_groups' => $professor->ageGroups->pluck('title')->toArray(), // گروه‌های سنی
                'language_levels' => $professor->languageLevels->pluck('title')->toArray(), // سطوح زبان
                'languages' => $professor->languages->pluck('title')->toArray(), // زبان‌ها
                'group_classes_count' => $professor->group_classes_count,
                'private_classes_count' => $professor->private_classes_count,
                'students_count' => $professor->students_count,
                'workshops_count' => $professor->workshops_count,
                'webinars_count' => $professor->webinars_count,
                'about_text' => $professor->about_text,
                'attendance' => $professor->attendance,
                'closest_time' => '30',
                'rate' => '3',
                'rate_count' => '30',
                'country' => 'ایران',
                'city' => 'تهران',
                'region' => 'صادقیه',
                'about_video' => $professor->about_video?->first()?->path? api::api . $professor->about_video->first()->path: null,
                'cover' => $professor->cover?->first()?->path? api::api . $professor->cover->first()->path: null,
                'profile' => $professor->profile?->first()?->path? api::api . $professor->profile->first()->path: null,
            ],
            'id' => $professor->id,
            'professor_code' => $professor->professor_code,
            'birth' => $professor->birth,
            'mobile' => $professor->mobile,
            'price' => $professor->price,
            'email' => $professor->email,
            'identification_code' => $professor->identification_code,
            'is_student' => $professor->is_student,
            'created_at' => $professor->created_at,
            'updated_at' => $professor->updated_at,
            'teaching_times' => $professor->teachingTimes,
            'teaching_example_video' => $professor->teaching_example_video?->first()?->path? api::api . $professor->teaching_example_video->first()->path: null,
            'home_video' => $professor->home_video?->first()?->path? api::api . $professor->home_video->first()->path: null,
            'school_video' => $professor->school_video?->first()?->path? api::api . $professor->school_video->first()->path: null,
            'point' => $languages
        ]);
    }

    public function getFilters(Request $request)
    {
        // زبان‌ها
        $languages = Languages::select('id', 'title')->get();

        // نوع تدریس
        $teachingTypes = TeachingType::select('id', 'title')->get();

        // گروه‌های سنی
        $ageGroups = AgeGroup::select('id', 'title')->get();

        // سطوح زبان
        $languageLevels = LanguageLevel::select('id', 'title')->get();

        // جنسیت‌ها
        $genders = Gender::select('id', 'title')->get();

        // لهجه‌ها
        $accent = Accent::select('id', 'title')->get();

        // اساتید
        $professor = Professor::get();

        // استان‌ها
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

        // بازه قیمت
        $minPrice = (int) $professor->min('price');
        $maxPrice = (int) $professor->max('price');

        // نوع تخفیف‌ها
        $discountTypes = DiscountType::select('id', 'title')->get();
        foreach ($discountTypes as &$discountType) {
            $discountType['value'] = $discountType->id;
        }
        $experimentDiscountTypes = ExperimentDiscountType::select('id', 'title')->get();
        foreach ($experimentDiscountTypes as &$discountType) {
            $discountType['value'] = $discountType->id;
        }

        // ایجاد پاسخ
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
            'provinces' => $provinces->toArray(),
            'cities' => $cities->toArray(),
            'assessmentDiscountTypes' => $discountTypes->toArray(),
            'experimentDiscountTypes' => $experimentDiscountTypes->toArray(), // اضافه کردن تخفیف‌های کلاس‌های آزمایشی

            'price_range' => [
                'min_price' => 0,
                'max_price' => $maxPrice,
            ],
        ];

        return api_response($result, 'فیلتر با موفقیت ارسال شد');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'teaching_type_id' => 'required|exists:teaching_types,id',
            'gender' => 'required|exists:genders,id',
            'age_group_id' => 'required|exists:age_groups,id',
            'language_level_id' => 'required|exists:language_levels,id',
            'subject_id' => 'required',
            'class_type' => 'required',
            'skill' => 'nullable',
            'affordable_type' => 'nullable',
            'session_count' => 'nullable',
            'discount_code' => 'nullable',
            'massage' => 'nullable',
        ]);
    }


}
