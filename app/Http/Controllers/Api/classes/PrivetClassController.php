<?php

namespace App\Http\Controllers\Api\classes;

use App\Helpers\zarinpal;
use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\DiscountForStudent;
use App\Models\Financial\Payment;
use App\Models\Financial\Transaction;
use App\Models\iran_regions\IranCity;
use App\Models\iran_regions\IranProvince;
use App\Models\Languages;
use App\Models\Like;
use App\Models\PlacementTest;
use App\Models\Professors\AgeGroupLanguageLevel;
use App\Models\Professors\BookLearning;
use App\Models\Professors\ClassPrice;
use App\Models\Professors\DiscountClass;
use App\Models\Professors\DiscountClassPrice;
use App\Models\Professors\DiscountType;
use App\Models\Professors\ExperimentDiscountType;
use App\Models\Professors\Gender;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningSubgoal;
use App\Models\Professors\Offline;
use App\Models\Professors\Online;
use App\Models\Professors\PlacementTestSkill;
use App\Models\Professors\PrivetClassBook;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\privetTeacherTimeSlot;
use App\Models\Professors\Professor;
use App\Models\Professors\ProfessorAuthor;
use App\Models\Professors\ProfessorBlog;
use App\Models\Professors\ProfessorCertificate;
use App\Models\Professors\ProfessorEducation;
use App\Models\Professors\ProfessorHonor;
use App\Models\Professors\ProfessorLearningGoal;
use App\Models\Professors\ProfessorRecord;
use App\Models\Professors\ProfessorTranslation1;
use App\Models\Professors\TeacherTimeSlot;
use App\Models\Professors\Webinar;
use App\Models\Professors\webinarsBook;
use App\Models\Rating;
use App\Models\shop\Accent;
use App\Models\shop\Comment;
use App\Models\shop\Story;
use App\Models\TeachingType;
use App\Services\SmsServiceServices;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrivetClassController extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->input('language' ,1);
        $query = Professor::visible()->with([
            'teachingTypes',
            'genders',
            'ageGroups',
            'languageLevels',
            'languages',
            'teacherTimeSlots',
//            'accent',
            'cover',
            'about_video',
            'aboutTexts',
            'Offline.country',
            'Offline.city',
            'ClassPrice',
            'gender_P',
            'addresses'

        ])->whereHas('languages', function ($q) use ($lang) {
            $q->where('professor_languages.language_id', $lang);
        })
            ->priorityOrdered();


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
            $query->where('first_name', 'like', '%' . $request->search . '%')
                ->orWhere('last_name', 'like', '%' . $request->search . '%');
        }
        $query->whereRelation('languages', 'languages.id', '=', $request->language);

        if ($request->filled('teachingTypes')) {
            $types = (array)$request->teachingTypes;

            // اگر نوع 3 بین‌شون بود، اصلاً فیلتر نکن
            if (!in_array(3, $types)) {
                // فقط اجازه بده اگر آیدی 1 یا 2 بودن
                $allowed = [1, 2];
                $filtered = array_intersect($types, $allowed);

                if (!empty($filtered)) {
                    $query->whereHas('teachingTypes', function ($q) use ($filtered) {
                        $q->whereIn('teaching_types.id', $filtered);
                    });
                }
            }
        }
//        if ($request->filled('accent')) {
//            $query->whereRelation('accent', 'accents.id', '=', $request->accent);
//        }
        if ($request->filled('near')) {
            $minutesMap = [
                1 => 30,
                2 => 60,
                3 => 240,
                4 => 720,
                5 => 1440,
            ];

            $nearKey = (int)$request->near;
            if (isset($minutesMap[$nearKey])) {
                $now = now();
                $target = now()->copy()->addMinutes($minutesMap[$nearKey]);

                $query->whereHas('teacherTimeSlots', function ($q) use ($now, $target) {
                    $q->whereRaw("STR_TO_DATE(CONCAT(date, ' ', time), '%Y-%m-%d %H:%i:%s') BETWEEN ? AND ?", [$now, $target]);
                });
            }
        }
        if ($request->filled('placement')) {
            $query->whereHas('placementTests', function ($q) use ($request , $lang) {
                if ($request->filled('language_id')) {
                    $q->where('language_id',$lang);
                }
            });
        }

        if ($request->filled('languageLevels')) {
            $query->whereRelation('ageGroupLanguageLevels', 'language_level_id', '=', $request->languageLevels);
        }
        if ($request->filled('age_group')) {
            $query->whereRelation('ageGroups', 'age_groups.id', '=', $request->age_group);
        }
        if ($request->filled('country')) {
            $query->whereRelation('addresses', 'country_id', '=', $request->country);
        }
        if ($request->filled('city')) {
            $query->whereRelation('addresses', 'city_id', '=', $request->city);
        }
        if ($request->filled('gender')) {
            if ($request->gender == 1) {
                $query->where('professors_gender', 'مرد');
            } elseif ($request->gender == 2) {
                $query->where('professors_gender', 'زن');
            } elseif ($request->gender == 3) {
                $query->whereIn('professors_gender', ['مرد', 'زن']);
            }
        }
        $isInside = $request->input('is_inside', 'in'); // پیش‌فرض 'in'

        if ($isInside === 'in') {
            $query->whereRelation('addresses', 'country_id', 1);
        } elseif ($isInside === 'out') {
            $query->whereRelation('addresses', 'country_id', '!=', 1);
        }

        if ($request->filled('closest_time')) {
            $query->whereHas('teacherTimeSlots', function ($subQuery) {
                $subQuery->where('date', '>=', now())
                    ->orderBy('date', 'asc')
                    ->orderBy('time', 'asc');
            });
        }
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereHas('ClassPrice', function ($query) use ($request) {
                $query->whereBetween('base_price', [$request->min_price, $request->max_price]);
            });
        } elseif ($request->filled('min_price')) {
            $query->whereHas('ClassPrice', function ($query) use ($request) {
                $query->where('base_price', '>', $request->min_price);
            });
        } elseif ($request->filled('max_price')) {
            $query->whereHas('ClassPrice', function ($query) use ($request) {
                $query->where('price', '<=', $request->max_price);
            });
        }
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'best_selling':
                    $query->orderByDesc('students_count');
                    break;
                case 'newest':
                    $query->orderByDesc('created_at');
                    break;
                case 'discounted':
                    $query->whereHas('discount', function ($q) {
                        $q->where('discount_percentage', '>', 0);
                    })
                        ->with('discount')
                        ->orderByDesc(
                            DiscountClass::select('discount_percentage')
                                ->whereColumn('discounts.professor_id', 'professors.id')
                                ->limit(1)
                        );
                    break;
                case 'most_viewed':
                    $query->orderByDesc('views_count');
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
        $professors->getCollection()->transform(function ($professor) use ($lang) {
            $offline = $professor->Offline->where('language_id', $lang)->first();
            $ageGroups = $professor->ageGroups
                ->where('pivot.language_id', $lang)
                ->pluck('title')
                ->toArray();

// گرفتن language levelها از جدول واسط
            $levelIds = \DB::table('age_group_language_levels')
                ->where('professor_id', $professor->id)
                ->where('language_id', $lang)
                ->pluck('language_level_id');

            $languageLevels = LanguageLevel::whereIn('id', $levelIds)
                ->orderedByCustomIds()
                ->pluck('title')
                ->toArray();

            $is_like = false;
            $rate = 0;
            if (auth()->user()) {
                $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $professor->id)->where('likeable_type', Professor::class)->exists();
                $rate = Rating::where('user_id', auth()->user()->id)->where('ratable_id', $professor->id)->where('ratable_type', Professor::class)->first();

                if ($rate) {
                    $rating = $rate->rating;
                } else {
                    $rating = 0;
                }
            }

            $numberOfRatings = $professor->ratings()->count();
            $averageRating = $professor->ratings()->avg('rating');
            $averageRating = round($averageRating, 1);
            $trialType = \App\Models\Professors\ClassType::where('professor_id', $professor->id)
                ->where('language_id', $lang)
                ->first()?->trial_type;
//            $placementTest = \App\Models\Professors\PlacementTest::whereHas('classType', function ($q) use ($professor, $lang) {
//                $q->where('professor_id', $professor->id)
//                    ->where('language_id', $lang);
//            })->first();
//
//            $placement_test_pricing = $placementTest?->price_type;

            $hasOnlineClass = $professor->online()->where('language_id', $lang)->exists();
            $hasOfflineClass = $professor->offline()->where('language_id', $lang)->exists();

            $teachingTypes = [];

            if ($hasOnlineClass) {
                $teachingTypes[] = ' آنلاین';
            }

            if ($hasOfflineClass) {
                $teachingTypes[] = ' حضوری';
            }
            $address = $professor->addresses->first();
            $hasPlacementTest = PlacementTest::where('professor_id', $professor)
                ->where('language_id', $lang)
                ->exists();

            $trial = $professor->trial->where('language_id',$lang)->first();

            if ($trial && $trial->is_trial) {
                $discount1 = $trial->discount;
            } else {
                $discount1 = null;
            }
            $placementRecord = $professor->placementTests()
                ->where('language_id', $lang)
                ->first();
            return [
                'name' => $professor->full_name(),
                'age_groups' => $ageGroups,
                'language_levels' => $languageLevels,
                'genders' => $professor->genders->where('pivot.language_id', $lang)->pluck('title')->toArray(),
                'languages' => $professor->languages->map(function ($language) {
                    return api::api.$language->flag;
                })->toArray(),
                'group_classes_count' => $professor->group_classes_count,
                'private_classes_count' => $professor->private_classes_count,
                'workshop_classes_count' => $professor->workshops_count,
                'webinar_classes_count' => $professor->webinars_count,
                'students_count' => $professor->students_count,
                'closest_time' => optional($professor->teacherTimeSlots->first())->date . ' ' . optional($professor->teacherTimeSlots->first())->time,
                'country' => $address?->country?->title,
                'city' =>$address?->city?->name,
                'region' =>$professor->addresses->first()?->district ?? '',
                'about_video' =>$professor->about_video->first()?->path
                    ? api::api . $professor->about_video->first()->path
                    : null,
                'about_text' => $professor->aboutTexts->where('language_id', $lang)->first()?->about_text,
                'cover' =>$professor->profile ?api::api . $professor->profile : null,
                'profile' => $professor->profile ?api::api . $professor->profile : null,
                'id' => $professor->id,
                'price' => $professor->ClassPrice->first()?->base_price ?? 0,
//                'price_discount' => $final_price ? $professor->ClassPrice->first()?->base_price: $professor->ClassPrice->first()?->base_price ,
//                'discount' => $discount_percentage ??null,
                'attendance' => $professor->attendance,
                'views_count' => $professor->views_count,
//                'professor_code' => ClassType::where('professor_id', $professor->id)->where('language_id', $lang)->first()?->,
                'is_like' => $is_like,
                'rate' => $rating ?? null,
                'rate_count' => $numberOfRatings ?? 0,
                'rate_av' => $averageRating ?? 0,
                'trial_class' => $discount1,
                'placement_test' => $placementRecord ? $placementRecord->price_fa : null,
                'teaching_types' => $teachingTypes,
                'video_course' => $professor->teaching_example_video
                    ? ($professor->teaching_example_video->where('language_id', $lang)->first()
                        ? api::api . $professor->teaching_example_video->where('language_id', $lang)->first()->path
                        : null)
                    : null,
                'cover_course' =>$professor->profile ?api::api . $professor->profile : null,

            ];
        });

        return api_response($professors);
    }

    public function show(Request $request, Professor $professor)
    {
        $professor->increment('views_count');
        $l = $professor->languages->first()->id;
        $lang = $request->input('language', $l);

        $languages = [];

        $language = $professor->languages->where('id', $lang)->first();

        if ($language) {
            $professorSubgoals = $professor->learningGoals->where('language_id', $language->id);

            $languageData = [];
            foreach ($professorSubgoals as $learningGoal) {
                $subgoal = $learningGoal->subgoal;

                if (!$subgoal || !$subgoal->goal) {
                    continue; // skip this one if subgoal or goal doesn't exist
                }

                $goal = $subgoal->goal;

                if (!isset($languageData[$goal->title])) {
                    $languageData[$goal->title] = [];
                }

                $languageData[$goal->title][$subgoal->sub][] = $subgoal->title;
            }


            $languages = $languageData;
        }

        $offline = $professor->Offline->where('language_id', $lang)->first();
        $ageGroups = $professor->ageGroups
            ->where('pivot.language_id', $lang)
            ->pluck('title')
            ->toArray();


        $levelIds = \DB::table('age_group_language_levels')
            ->where('professor_id', $professor->id)
            ->where('language_id', $lang)
            ->pluck('language_level_id');

        $languageLevels = LanguageLevel::whereIn('id', $levelIds)
            ->pluck('title')
            ->toArray();
        $is_like = false;
        $rate = 0;
        if (auth()->user()) {
            $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $professor->id)->where('likeable_type', Professor::class)->exists();
            $rate = Rating::where('user_id', auth()->user()->id)->where('ratable_id', $professor->id)->where('ratable_type', Professor::class)->first();

            if ($rate) {
                $rating = $rate->rating;
            } else {
                $rating = 0;
            }
        }

        $numberOfRatings = $professor->ratings()->count();
        $averageRating = $professor->ratings()->avg('rating');
        $averageRating = round($averageRating, 1);
        $trialType = \App\Models\Professors\ClassType::where('professor_id', $professor->id)
            ->where('language_id', $lang)
            ->first()?->trial_type;
//        $placementTest = \App\Models\Professors\PlacementTest::whereHas('classType', function ($q) use ($professor, $lang) {
//            $q->where('professor_id', $professor->id)
//                ->where('language_id', $lang);
//        })->first();

//        $placement_test_pricing = $placementTest?->price_type;

        $hasOnlineClass = $professor->online()->where('language_id', $lang)->exists();
        $hasOfflineClass = $professor->offline()->where('language_id', $lang)->exists();

        $teachingTypes = [];

        if ($hasOnlineClass) {
            $teachingTypes[] = ' آنلاین';
        }

        if ($hasOfflineClass) {
            $teachingTypes[] = ' حضوری';
        }

        $stories = Story::with([ 'likes'])
            ->where('is_approved', true)
            ->where('user_id', $professor->user->id)
            ->get();
        $user = auth()->user();

        $formattedStories = $stories->map(function ($story) use ($user) {
            $commentsQuery = Comment::with(['user', 'likes'])
                ->where('commentable_type', Story::class)
                ->where('commentable_id', $story->id);

            $comments = $commentsQuery->get();

            return [
                'id' => $story->id,
                'cover' => $story->cover ? asset($story->cover) : api::api.$story->user->professor->profile,
                'title' => $story->title,
                'likes_count' => $story->likes()->count(),
                'is_liked' => $user ? $story->likes()->where('user_id', $user->id)->exists() : false,
                'comments_count' => $comments->count(),
                'comments' => $comments->map(function ($comment) use ($user) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->getFullName(),
                        'body' => $comment->body,
                        'profile_image' => $comment->user->student && $comment->user->student->profile
                            ? api::api . $comment->user->student->profile
                            : null,
                        'likes_count' => $comment->likes()->count(),
                        'is_liked' => $user ? $comment->likes()->where('user_id', $user->id)->exists() : false,
                        'is_mine' => $user ? $comment->user_id == $user->id : false,
                        'created_at' => $comment->created_at,
                    ];
                }),
            ];
        });
        $languageIds = $professor->languages->pluck('id');

        $related = Professor::visible()->where('id', '!=', $professor->id)
            ->whereHas('languages', function ($query) use ($languageIds) {
                $query->whereIn('languages.id', $languageIds);
            })
            ->with('languages')
            ->take(4)
            ->get()
            ->map(function ($relatedProfessor) {
                $user = auth()->user();
                $is_like = false;
                $rating = 0;

                if ($user) {
                    $is_like = Like::where('user_id', $user->id)
                        ->where('likeable_id', $relatedProfessor->id)
                        ->where('likeable_type', Professor::class)
                        ->exists();

                    $rate = Rating::where('user_id', $user->id)
                        ->where('ratable_id', $relatedProfessor->id)
                        ->where('ratable_type', Professor::class)
                        ->first();

                    $rating = $rate ? $rate->rating : 0;
                }

                return [
                    'id' => $relatedProfessor->id,
                    'name' => $relatedProfessor->full_name(),
                    'views_count' => $relatedProfessor->views_count,
                    'profile' => $relatedProfessor->profile ? 'https://api.lingomasters.ir/' . $relatedProfessor->profile : null,
                    'is_liked' => $is_like,
                    'address' => optional($relatedProfessor->offline->first()?->city)->name . '-' . optional($relatedProfessor->offline->first()?->country)->title,
                    'price' => $relatedProfessor->ClassPrice->where('class_type', 'private')?->first()?->base_price ?? 'قیمتی تعیین نشده',
                    'rating' => $rating,
                    'languages' => $relatedProfessor->languages->first()->title,
                    'flag' => $relatedProfessor->languages->first()->icon,

                ];
            });

        $books = BookLearning::where('professor_id', $professor->id)
            ->with(['product.language', 'product.goal', 'product.age_group', 'product.images', 'language', 'subgoal', 'ageGroup', 'languageLevel'])
            ->get();

        $a = $books->map(function ($book) {
            if (!empty($book->product_id) && $book->product) {
                return [
                    'id' => $book->product->id,
                    'title' => $book->product->title,
                    'language_id' => optional($book->product->language)->title,
                    'subject' => optional($book->product->goal)->title,
                    'age_group' => optional($book->product->age_group)->title,
                    'language_level' => optional($book->product->language_level)->title, // این از book هست
                    'images' => $book->product->images->first()
                        ? api::api . $book->product->images->first()->path
                        : null,
                ];
            } else {
                return [
                    'id' => $book->id,
                    'title' => $book->title,
                    'language_id' => optional($book->language)->title,
                    'subject' => optional($book->subgoal)->title,
                    'age_group' => optional($book->ageGroup)->title,
                    'language_level' => optional($book->languageLevel)->title,
                    'images' => $book->product && $book->product->images->first()
                        ? api::api . $book->product->images->first()->path
                        : null,
                ];
            }
        });


        $discount = $professor->discount->first();

        $discount_percentage = $discount ? $discount->discount_percentage : 0;

        $final_price = $professor->price - ($professor->price * ($discount_percentage / 100));
        $trial = $professor->trial->where('language_id',$lang)->first();

        if ($trial && $trial->is_trial) {
            $discount1 = $trial->discount;
        } else {
            $discount1 = null;
        }

        $placement = $professor->placementTests->where('language_id', $lang)->first();

        if ($placement) {
            $discount2 = $placement->price; // مقدار اصلی (مثلاً 'free')
            $discount2_fa = $placement->price_fa; // معادل فارسی (مثلاً 'رایگان')
        } else {
            $discount2 = null;
            $discount2_fa = null;
        }
        $in_person = $professor->nearestClasses->where('language_id', $lang)->where('type','in_person')->first();
        $online = $professor->nearestClasses->where('language_id', $lang)->where('type','online')->first();$on = '';
        $on = '';
        if ($online) {
            $on = $online->title == 0.5 ? '30 دقیقه' : $online->title . ' ساعت';
        }

        $in = '';
        if ($in_person) {
            $in = $in_person->title == 0.5 ? '30 دقیقه' : $in_person->title . ' ساعت';
        }
        $address = $professor->addresses->first();

        return api_response([
            'about' => [
                'trial_class'  => $discount1,
                'placement_test'  => $discount2_fa,
                'closest_time_online' => $on,
                'closest_time_offline' => $in,
                'views_count' => $professor->views_count,
                'name' => $professor->full_name(),
                'genders' => $professor->genders->where('pivot.language_id', $lang)->pluck('title')->toArray(),
                'teaching_types' => $teachingTypes,
                'age_groups' => $ageGroups,
                'language_levels' => $languageLevels,
                'languages' => $professor->languages->map(function ($language) {
                    return [
                        'id' => $language->id,
                        'title' => $language->title,
                        'flag' => api::api.$language->flag,
                    ];
                }),
                'group_classes_count' => $professor->group_classes_count,
                'private_classes_count' => $professor->private_classes_count,
                'students_count' => $professor->students_count,
                'workshops_count' => $professor->workshops_count,
                'webinars_count' => $professor->webinars_count,
                'attendance' => $professor->attendance,
                'rate' => $rating ?? null,
                'rate_count' => $numberOfRatings ?? 0,
                'country' => $address?->country?->title,
                'city' =>$address?->city?->name,
                'district' => $professor->addresses->first()?->district ?? '',

                'region' => Offline::where('professor_id', $professor->id)->where('language_id', $lang)->pluck('region')->toArray(),
                'about_video' =>$professor->about_video->first()?->path
                    ? api::api . $professor->about_video->first()->path
                    : null,
                'about_text' => $professor->aboutTexts->where('language_id', $lang)->first()?->about_text,
                'cover' =>  $professor->profile ? api::api  . $professor->profile : null,
                'profile' => $professor->profile ? api::api  . $professor->profile : null,
            ],
            'id' => $professor->id,
            'professor_code' => $professor->professor_code,
            'birth' => $professor->birth,
            'mobile' => $professor->mobile,
            'price' => $professor->ClassPrice->first()?->base_price ?? 0,
            'price_discount' => $final_price ? $professor->ClassPrice->first()?->base_price: $professor->ClassPrice->first()?->base_price ,
            'discount' => $discount_percentage ??null,
            'email' => $professor->email,
            'platform' => Online::where('professor_id', $professor->id)->where('language_id', $lang)->pluck('platform_name')->toArray(),
            'address' => Offline::where('professor_id', $professor->id)
                ->where('language_id', $lang)
                ->select(['type', 'video', 'latitude', 'longitude'])
                ->get()
                ->map(function ($offline) {
                    return [
                        'type' => $offline->type,
                        'video' => $offline->video ? api::api . $offline->video : null,
                        'latitude' => $offline->latitude,
                        'longitude' => $offline->longitude,
                    ];
                }),
            'identification_code' => $professor->identification_code,
            'is_student' => $professor->is_student,
            'created_at' => $professor->created_at,
            'updated_at' => $professor->updated_at,
            'teaching_times' => $professor->teachingTimes,
            'teaching_example_video' => $professor->teaching_example_video
                ? ($professor->teaching_example_video->where('language_id', $lang)->first()
                    ? api::api . $professor->teaching_example_video->where('language_id', $lang)->first()->path
                    : null)
                : null,
            'teaching_example_cover' =>$professor->profile ?api::api . $professor->profile : null,
            'point' => $languages,
            'books' => $a,
            'skills' => [
                'blogs' => ProfessorBlog::where('professors_id', $professor->id)
                    ->where('language_id', $lang)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                        'is_persian' => $blog->is_persian,
                    ])->groupBy('is_persian'),
                'honor' => ProfessorHonor::where('professors_id', $professor->id)->where('language_id', $lang)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                    ]),

                'certificate' => ProfessorCertificate::where('professors_id', $professor->id)
                    ->where('language_id', $lang)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                        'in_iran' => $blog->in_iran
                    ])->groupBy('in_iran'),
                'translation' => ProfessorTranslation1::where('professors_id', $professor->id)->where('language_id', $lang)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                    ]),
                'records' => ProfessorRecord::where('language_id', $language->id)->
                    where('professors_id', $professor->id)
                    ->get()
                    ->groupBy('type')
                    ->map(function ($typeRecords, $type) {
                        $totalDuration = 0;

                        foreach ($typeRecords as $record) {
                            if ($record->start_date && $record->end_date) {
                                $startDate = \Carbon\Carbon::parse($record->start_date);
                                $endDate = \Carbon\Carbon::parse($record->end_date);

                                $duration = $endDate->diff($startDate);
                                $totalDuration += $duration->y + ($duration->m / 12) + ($duration->d / 365);
                            }
                        }

                        return [
                            'type' => $type,
                            'total_duration' => $totalDuration < 1 ? 'کمتر از 1' : round($totalDuration),
                        ];
                    })->values(),
                'education' => ProfessorEducation::where('professors_id', $professor->id)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->city,
                        'university' => $blog->university,
                        'lastest_evidence' => $blog->lastest_evidence,
                    ]),
                'author' => ProfessorAuthor::where('professors_id', $professor->id)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                    ]),
            ],

            'story' => $formattedStories,
            'related' => $related
        ]);
    }


    public function getFilters(Request $request)
    {
        $languages = Languages::select('id', 'title')->get();

        $teachingTypes = TeachingType::select('id', 'title')->get();

        $ageGroups = AgeGroup::select('id', 'title')->get();

        $languageLevels = LanguageLevel::select('id', 'title')->orderedByCustomIds()->get();

        $genders = Gender::select('id', 'title')->get();

        $accent = Accent::select('id', 'title')->get();

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
        $minPrice = (int)$professor->min('price');
        foreach ($professor as $professors) {
            $maxPrice = (int)$professors->ClassPrice()->max('base_price');
            // انجام کارهای دیگر با $maxPrice
        }
        $maxPrice = ClassPrice::where('class_type','private')->max('base_price');
        $discountTypes = DiscountType::select('id', 'title')->get();
        foreach ($discountTypes as &$discountType) {
            $discountType['value'] = $discountType->id;
        }
        $experimentDiscountTypes = ExperimentDiscountType::select('id', 'title')->get();
        foreach ($experimentDiscountTypes as &$discountType) {
            $discountType['value'] = $discountType->id;
        }
        $nearOptions = [
            ['value' => 1, 'name' => '۳۰ دقیقه آینده'],
            ['value' => 2, 'name' => '۱ ساعت آینده'],
            ['value' => 3, 'name' => '۴ ساعت آینده'],
            ['value' => 4, 'name' => '۱۲ ساعت آینده'],
            ['value' => 5, 'name' => '۲۴ ساعت آینده'],
        ];
        // ایجاد پاسخ
        $result = [
            'teachingTypes' => $teachingTypes->map(fn($cat) => [
                'value' => $cat->id,
                'name' => $cat->title,
            ])->toArray(),
            'near' => $nearOptions,

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


    public function toggleLike(Request $request, $id)
    {
        $group = Professor::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return api_response([], 'برای لایک کردن لاگین کنید', 401);
        }

        $like = $user->likes()
            ->where('likeable_id', $group->id)
            ->where('likeable_type', Professor::class)
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

    public function comment(Request $request, $groupClassId)
    {

        $request->validate([
            'body' => 'nullable|string|min:1|max:400', // حداقل طول 1 برای جلوگیری از رشته خالی
            'video_url' => 'nullable|file',
            'voice_url' => 'nullable|file',
        ], [
            'body.min' => 'توضیحات نباید خالی باشد.',
            'body.required_without_all' => 'توضیحات یا ویدیو یا ویس باید ارسال شود.',
            'video_url.required_without_all' => 'توضیحات یا ویدیو یا ویس باید ارسال شود.',
            'voice_url.required_without_all' => 'توضیحات یا ویدیو یا ویس باید ارسال شود.',
        ], [
            'body' => 'توضیحات',
            'video_url' => 'ویدیو',
            'voice_url' => 'ویس',
        ]);


        $groupClass = Professor::findOrFail($groupClassId);

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

    public function showComments(Request $request, $id)
    {
        $user_id = auth()->id();

        $groupClass = Professor::findOrFail($id);


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


    public function ditiles(Request $request, Professor $professor)
    {
        $language_input = $request->input('language');
        $age = $request->input('ageGroup');
        $goal = $request->input('subject_id');
        $type = $request->input('teaching_types');
        $level = $request->input('level');

        if (!$language_input) {
            $firstLanguage = $professor->languages->first();
            if (!$firstLanguage) {
                return api_response([], 'No language found for this professor');
            }
            $language_input = $firstLanguage->id;
        }

        $p1 = $this->showSelectedOnly($professor->id);
        $languages1 = $professor->languages->map(function ($language) {
            return [
                'id' => $language->id,
                'title' => $language->title,
                'flag' => api::api.$language->flag,
            ];
        });


        $discount = DiscountClassPrice::where('professor_id', $professor->id)
//            ->whereRelation('classPrice', 'language_id', '=', $language_input)
            ->where('class_mode', 'private')
            ->first();

        $discountData = [
            ['id' => '10', 'title' => $discount->discount_for_10 ?? null],
            ['id' => '15', 'title' => $discount->discount_for_15 ?? null],
            ['id' => '20', 'title' => $discount->discount_for_20 ?? null],
        ];


        $ageGroups = $professor->ageGroups
            ->where('pivot.language_id', $language_input)
            ->values()
            ->map(function ($item) {
                unset($item->pivot);
                return $item;
            })
            ->all();


        $levelIds = AgeGroupLanguageLevel::
        where('professor_id', $professor->id)
            ->where('language_id', $language_input)
            ->where('age_group_id', $age)
            ->pluck('language_level_id');

        $languageLevels = LanguageLevel::whereIn('id', $levelIds)
            ->get();

        $hasOnlineClass = $professor->online()->where('language_id', $language_input)->exists();
        $hasOfflineClass = $professor->offline()->where('language_id', $language_input)->exists();
        $teachingTypes = [];

        if ($hasOnlineClass) {
            $teachingTypes[] = [
                'id' => 2,
                'title' => 'آنلاین',
            ];
        }

        if ($hasOfflineClass) {
            $teachingTypes[] = [
                'id' => 1,
                'title' => 'حضوری',
            ];
        }


        $books = BookLearning::where('professor_id', $professor->id)
            ->where('status', 'approved')
            ->where('subgoal_id', $goal)
            ->where('language_id', $language_input)
            ->get();


        $classTypesDetails = [];

        if ($discount) {
            $classTypesDetails[] = [
                'id' => "1",
                'title' => "کلاس آزمایشی",
            ];
        }

//        $placementTests = PlacementTest::whereRelation('classType', 'professor_id', '=', $professor->id)
//            ->whereRelation('classType', 'language_id', '=', $language_input)
//            ->with('skills') // فرض بر این که relation به مهارت‌ها داره
//            ->get();
//        if ($placementTests->isNotEmpty()) {
//            $skills = collect();
//
//            foreach ($placementTests as $test) {
//                foreach ($test->skills as $skill) {
//                    $skills->push([
//                        'id' => (string)$skill->id,
//                        'title' => $skill->name,
//                    ]);
//                }
//            }
//
//            $uniqueSkills = $skills->unique('title')->values()->all();
//
//            $classTypesDetails[] = [
//                'id' => "2",
//                'title' => "کلاس تعیین سطح",
//                'content' => [
//                    [
//                        'title' => 'کلاس تعیین سطح',
//                        'data' => [
//                            ['id' => "speaking", 'title' => 'مهارت‌های گفتاری'],
//                            ['id' => "listening", 'title' => 'مهارت‌های شنیداری'],
//                            ['id' => "reading", 'title' => 'مهارت‌های نوشتاری'],
//                            ['id' => "writing", 'title' => 'مهارت‌های خواندن و نوشتن'],
//                        ]
//                    ]
//                ]
//            ];
//
//        }
//        $placementTests = PlacementTest::query()
//            ->where('professor_id', $professor->id)        // استاد
//            ->where('language_id', $language_input)        // زبان
//            ->with('skills:id,name')                       // فقط ستون‌های لازم را لود کن
//            ->get();
//
//        if ($placementTests->isNotEmpty()) {
//
//            // همهٔ مهارت‌ها را یکجا جمع و یکتا کن
//            $uniqueSkills = $placementTests
//                ->flatMap->skills        // شبیه به collect()->flatten() ولی روی relation
//                ->unique('id')           // یکتاسازی بر اساس id
//                ->map(fn ($skill) => [
//                    'id'    => (string) $skill->id,
//                    'title' => $skill->name,
//                ])
//                ->values()               // ریست ایندکس‌ها
//                ->all();
//
//            // خروجی نهایی برای کلاس تعیین سطح
//            $classTypesDetails[] = [
//                'id'      => '2',
//                'title'   => 'کلاس تعیین سطح',
//                'content' => [
//                    [
//                        'title' => 'کلاس تعیین سطح',
//                        'data'  => $uniqueSkills,   // لیست مهارت‌های یکتا
//                    ],
//                ],
//            ];
//        }
// کلاس خصوصی - برای کلاس اقتصادی و یک یا چند جلسه‌ای
        $hasPrivateClass = $professor->ClassPrice()
            ->where('language_id', $language_input)
            ->where('class_type', 'private')
            ->exists();

        if ($hasPrivateClass) {
            $classTypesDetails[] = [
                'id' => "3",
                'title' => "کلاس مقرون به صرفه",
                'content' => [
                    [
                        'title' => 'رزرو کلاس اقتصادی',
                        'data' => [
                            ['id' => '10', 'title' => $discount && $discount->discount_for_10 ? '10 جلسه ' . $discount->discount_for_10 . '% تخفیف' : null],
                            ['id' => '15', 'title' => $discount && $discount->discount_for_15 ? '15 جلسه ' . $discount->discount_for_15 . '% تخفیف' : null],
                            ['id' => '20', 'title' => $discount && $discount->discount_for_20 ? '20 جلسه ' . $discount->discount_for_20 . '% تخفیف' : null],

                        ]
                    ]
                ]
            ];


            $classTypesDetails[] = [
                'id' => "4",
                'title' => "کلاس یک یا چند جلسه‌ای",
                'content' => [
                    [
                        'title' => 'تعداد جلسات',
                        'data' => null
                    ]
                ]
            ];
        }


        $discount = DiscountClassPrice::where('professor_id', $professor->id)
            ->where('class_mode', 'private')
            ->value('discount_percentage');

        $classPrice = ClassPrice::where('professor_id', $professor->id)
            ->where('language_id', $language_input)
            ->where('class_type', 'private') // چون کلاس گروهی است
            ->first();

        $base_price = ClassPrice::where('professor_id', $professor->id)
            ->where('language_id', $language_input)
            ->where('class_type', 'private') // چون کلاس گروهی است
            ->value('base_price');
        if (!$classPrice) {
            return api_response(null, 'شهریه ای برای کلاس  ثبت نشده است', 404);
        }
        $priceForLevel = match ((int)$level) {
            1 => $classPrice->beginner_price,
            2 => $classPrice->intermediate_price,
            3 => $classPrice->advanced_price,
            default => null,
        };

        // قیمت بر اساس گروه سنی
        $priceForAge = match ((int)$age) {
            1 => $classPrice->child_price,
            2 => $classPrice->teen_price,
            3 => $classPrice->adult_price,
            default => null,
        };

        // قیمت بر اساس محل برگزاری
        $priceForPlace = match ((int)$type) {
            2 => $classPrice->online_price ?? 0,
            1 => $classPrice->intermediate_price ?? 0,
            default => 0,
        };

        $total = $priceForAge + $priceForLevel + $priceForPlace;
        $total_price = $total + $base_price;

//        $p = PlacementTest::where('language_id', $language_input)->where('age_group_id',)

        $p = PlacementTest::where('language_id', $language_input)
            ->where('age_group_id', $age)
            ->where('professor_id', $professor->id)
            ->first(); // یا ->firstOrFail()

        if ($p) {
            $skills = $p->skills()->select('skills.id', 'name')->get();
        }
        return api_response([
            'skill' => $skills ?? [],
            'teaching_types' => $teachingTypes,
            'age_groups' => $ageGroups,
            'language_levels' => $languageLevels,
            'platform' => Online::where('professor_id', $professor->id)->where('language_id', $language_input)->pluck('platform_name')->toArray(),
            'address' => Offline::where('professor_id', $professor->id)
                ->where('language_id', $language_input)
                ->select(['type', 'video', 'latitude', 'longitude', 'id'])
                ->get()
                ->map(function ($offline) {
                    return [
                        'id' => $offline->id,
                        'type' => $offline->type,
                        'video' => $offline->video ? api::api . $offline->video : null,
                        'latitude' => $offline->latitude,
                        'longitude' => $offline->longitude,
                    ];
                }),
            'point' => $p1,
            'books' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language?->title,
                'subject' => $book->subgoal?->title,
                'age_group' => $book->ageGroup?->title,
                'language_level' => $book->languageLevel?->title,
                'images' => $book->product?->images ?? null,
            ]),
            'discount' => $discountData,
            'class_types_details' => $classTypesDetails,
            'languages' => $languages1,
            'professor' => [
                'name' => $professor->full_name(),
                'profile' => $professor->profile ? api::api . $professor->profile : null,
                'id' => $professor->id,
                'price' => round($total_price),
                'discount' => $discount ?? null,
                'price_discount' => round($total_price - ($total_price * $discount / 100)) ?? null,
            ]


        ]);

    }

    public function ditiles2(Request $request, Professor $professor)
    {
        $language_input = $request->input('language');
        $age = $request->input('ageGroup');
        $goal = $request->input('subject_id');
        $type = $request->input('teaching_types');
        $level = $request->input('level');

        if (!$language_input) {
            $firstLanguage = $professor->languages->first();
            if (!$firstLanguage) {
                return api_response([], 'No language found for this professor');
            }
            $language_input = $firstLanguage->id;
        }

        $p = $this->showSelectedOnly($professor->id);
        $languages1 = $professor->languages->map(function ($language) {
            return [
                'id' => $language->id,
                'title' => $language->title,
                'flag' => api::api.$language->flag,
            ];
        });


        $discount = DiscountClassPrice::where('professor_id', $professor->id)
//            ->whereRelation('classPrice', 'language_id', '=', $language_input)
            ->where('class_mode', 'private')
            ->first();

        $discountData = [
            ['id' => '10', 'title' => $discount->discount_for_10 ?? null],
            ['id' => '15', 'title' => $discount->discount_for_15 ?? null],
            ['id' => '20', 'title' => $discount->discount_for_20 ?? null],
        ];


        $ageGroups = $professor->ageGroups
            ->where('pivot.language_id', $language_input)
            ->values()
            ->map(function ($item) {
                unset($item->pivot);
                return $item;
            })
            ->all();


        $levelIds = AgeGroupLanguageLevel::
        where('professor_id', $professor->id)
            ->where('language_id', $language_input)
            ->where('age_group_id', $age)
            ->pluck('language_level_id');

        $languageLevels = LanguageLevel::whereIn('id', $levelIds)
            ->where('id','!=',4)
            ->get();

        $hasOnlineClass = $professor->online()->where('language_id', $language_input)->exists();
        $hasOfflineClass = $professor->offline()->where('language_id', $language_input)->exists();
        $teachingTypes = [];

        if ($hasOnlineClass) {
            $teachingTypes[] = [
                'id' => 2,
                'title' => 'آنلاین',
            ];
        }

        if ($hasOfflineClass) {
            $teachingTypes[] = [
                'id' => 1,
                'title' => 'حضوری',
            ];
        }


        $books = BookLearning::where('professor_id', $professor->id)
            ->where('status', 'approved')
            ->where('subgoal_id', $goal)
            ->where('language_id', $language_input)
            ->get();


        $classTypesDetails = [];

        if ($discount) {
            $classTypesDetails[] = [
                'id' => "1",
                'title' => "کلاس آزمایشی",
            ];
        }

//        $placementTests = PlacementTest::whereRelation('classType', 'professor_id', '=', $professor->id)
//            ->whereRelation('classType', 'language_id', '=', $language_input)
//            ->with('skills') // فرض بر این که relation به مهارت‌ها داره
//            ->get();
//        if ($placementTests->isNotEmpty()) {
//            $skills = collect();
//
//            foreach ($placementTests as $test) {
//                foreach ($test->skills as $skill) {
//                    $skills->push([
//                        'id' => (string)$skill->id,
//                        'title' => $skill->name,
//                    ]);
//                }
//            }
//
//            $uniqueSkills = $skills->unique('title')->values()->all();
//
//            $classTypesDetails[] = [
//                'id' => "2",
//                'title' => "کلاس تعیین سطح",
//                'content' => [
//                    [
//                        'title' => 'کلاس تعیین سطح',
//                        'data' => [
//                            ['id' => "speaking", 'title' => 'مهارت‌های گفتاری'],
//                            ['id' => "listening", 'title' => 'مهارت‌های شنیداری'],
//                            ['id' => "reading", 'title' => 'مهارت‌های نوشتاری'],
//                            ['id' => "writing", 'title' => 'مهارت‌های خواندن و نوشتن'],
//                        ]
//                    ]
//                ]
//            ];
//
//        }
        $placementTests = PlacementTest::query()
            ->where('professor_id', $professor->id)        // استاد
            ->where('language_id', $language_input)        // زبان
            ->with('skills:id,name')                       // فقط ستون‌های لازم را لود کن
            ->get();

        if ($placementTests->isNotEmpty()) {

            // همهٔ مهارت‌ها را یکجا جمع و یکتا کن
            $uniqueSkills = $placementTests
                ->flatMap->skills        // شبیه به collect()->flatten() ولی روی relation
                ->unique('id')           // یکتاسازی بر اساس id
                ->map(fn ($skill) => [
                    'id'    => (string) $skill->id,
                    'title' => $skill->name,
                ])
                ->values()               // ریست ایندکس‌ها
                ->all();

            // خروجی نهایی برای کلاس تعیین سطح
            $classTypesDetails[] = [
                'id'      => '2',
                'title'   => 'کلاس تعیین سطح',
                'content' => [
                    [
                        'title' => 'کلاس تعیین سطح',
                        'data'  => $uniqueSkills,   // لیست مهارت‌های یکتا
                    ],
                ],
            ];
        }
// کلاس خصوصی - برای کلاس اقتصادی و یک یا چند جلسه‌ای
        $hasPrivateClass = $professor->ClassPrice()
            ->where('language_id', $language_input)
            ->where('class_type', 'private')
            ->exists();

        if ($hasPrivateClass) {
            $classTypesDetails[] = [
                'id' => "3",
                'title' => "کلاس مقرون به صرفه",
                'content' => [
                    [
                        'title' => 'رزرو کلاس اقتصادی',
                        'data' => [
                            ['id' => '10', 'title' => $discount && $discount->discount_for_10 ? '10 جلسه ' . $discount->discount_for_10 . '% تخفیف' : null],
                            ['id' => '15', 'title' => $discount && $discount->discount_for_15 ? '15 جلسه ' . $discount->discount_for_15 . '% تخفیف' : null],
                            ['id' => '20', 'title' => $discount && $discount->discount_for_20 ? '20 جلسه ' . $discount->discount_for_20 . '% تخفیف' : null],

                        ]
                    ]
                ]
            ];


            $classTypesDetails[] = [
                'id' => "4",
                'title' => "کلاس یک یا چند جلسه‌ای",
                'content' => [
                    [
                        'title' => 'تعداد جلسات',
                        'data' => null
                    ]
                ]
            ];
        }


        $discount = DiscountClassPrice::where('professor_id', $professor->id)
            ->where('class_mode', 'private')
            ->value('discount_percentage');

        $classPrice = ClassPrice::where('professor_id', $professor->id)
            ->where('language_id', $language_input)
            ->where('class_type', 'private') // چون کلاس گروهی است
            ->first();

        $base_price = ClassPrice::where('professor_id', $professor->id)
            ->where('language_id', $language_input)
            ->where('class_type', 'private') // چون کلاس گروهی است
            ->value('base_price');
        if (!$classPrice) {
            return api_response(null, 'شهریه ای برای کلاس  ثبت نشده است', 404);
        }
        $priceForLevel = match ((int)$level) {
            1 => $classPrice->beginner_price,
            2 => $classPrice->intermediate_price,
            3 => $classPrice->advanced_price,
            default => null,
        };

        // قیمت بر اساس گروه سنی
        $priceForAge = match ((int)$age) {
            1 => $classPrice->child_price,
            2 => $classPrice->teen_price,
            3 => $classPrice->adult_price,
            default => null,
        };

        // قیمت بر اساس محل برگزاری
        $priceForPlace = match ((int)$type) {
            2 => $classPrice->online_price ?? 0,
            1 => $classPrice->intermediate_price ?? 0,
            default => 0,
        };

        $total = $priceForAge + $priceForLevel + $priceForPlace;
        $total_price = $total + $base_price;

//        $p = PlacementTest::where('language_id', $language_input)->where('age_group_id',)

        $p = PlacementTest::where('language_id', $language_input)
            ->where('age_group_id', $age)
            ->where('professor_id', $professor->id)
            ->first(); // یا ->firstOrFail()

        if ($p) {
            $skills = $p->skills()->select('skills.id', 'name')->get();
        }
        return api_response([
            'skill' => $skills ?? [],
            'teaching_types' => $teachingTypes,
            'age_groups' => $ageGroups,
            'language_levels' => $languageLevels,
            'platform' => Online::where('professor_id', $professor->id)->where('language_id', $language_input)->pluck('platform_name')->toArray(),
            'address' => Offline::where('professor_id', $professor->id)
                ->where('language_id', $language_input)
                ->select(['type', 'video', 'latitude', 'longitude', 'id'])
                ->get()
                ->map(function ($offline) {
                    return [
                        'id' => $offline->id,
                        'type' => $offline->type,
                        'video' => $offline->video ? api::api . $offline->video : null,
                        'latitude' => $offline->latitude,
                        'longitude' => $offline->longitude,
                    ];
                }),


            'discount' => $discountData,

            'languages' => $languages1,
            'professor' => [
                'name' => $professor->full_name(),
                'profile' => $professor->profile ? api::api . $professor->profile : null,
                'id' => $professor->id,
                'price' => round($total_price),
                'discount' => $discount ?? null,
                'price_discount' => round($total_price - ($total_price * $discount / 100)) ?? null,
            ]


        ]);

    }

    private function showSelectedOnly($professor)
    {
        $professorId = $professor;

        $professorSelectedGoals = ProfessorLearningGoal::where('professor_id', $professorId)
            ->pluck('subgoal_id')
            ->toArray();

        $selectedSubgoals = LearningSubgoal::with('goal')
            ->whereIn('id', $professorSelectedGoals)
            ->get()
            ->groupBy('goal.id');

        $data = [
            "goals" => [],
            "exams" => [
                [
                    "id" => "1",
                    "title" => "داخل ایران",
                    "content" => []
                ],
                [
                    "id" => "2",
                    "title" => "بین المللی",
                    "content" => []
                ]
            ]
        ];

        $goalCategories = [
            1 => "فنون زبان",
            2 => "مهارت ها",
            3 => "زبان مدرسه",
            4 => "زبان دانشگاه",
            5 => "زبان کنکور",
            6 => "ترجمه",
            7 => "آزمون ها",
            8 => "مشاغل",
            9 => "مصاحبه",
            10 => "لهجه و تلفظ",
            11 => "تربیت مدرس TTC",
        ];

        foreach ($goalCategories as $goalId => $goalTitle) {
            // فقط اگر استاد چیزی انتخاب کرده باشه، نمایش بده
            if (!$selectedSubgoals->has($goalId)) {
                continue;
            }

            $goalData = [
                "id" => $goalId,
                "title" => $goalTitle,
                "content" => ($goalId === 7) ? " " : [],
            ];

            if ($goalId !== 7) {
                $subgoals = $selectedSubgoals[$goalId]->groupBy('sub');

                foreach ($subgoals as $subTitle => $groupedSubgoals) {
                    $subgroupData = [
                        "title" => $subTitle ?: "بدون دسته‌بندی",
                        "data" => [],
                    ];

                    foreach ($groupedSubgoals as $subgoal) {
                        $subgroupData["data"][] = [
                            "title" => $subgoal->title,
                            "id" => $subgoal->id,
                        ];
                    }

                    $goalData["content"][] = $subgroupData;
                }
            }

            if ($goalId === 7) {
                $examGroups = $selectedSubgoals[$goalId]->groupBy('sub');

                foreach ($examGroups as $subTitle => $groupedSubgoals) {
                    $subgroupData = [
                        "title" => $subTitle ?: "بدون دسته‌بندی",
                        "data" => [],
                    ];

                    foreach ($groupedSubgoals as $subgoal) {
                        $subgroupData["data"][] = [
                            "title" => $subgoal->title,
                            "id" => $subgoal->id,
                        ];
                    }

                    if (strpos($subTitle, 'داخل ایران') !== false) {
                        $data["exams"][0]["content"][] = $subgroupData;
                    } else {
                        $data["exams"][1]["content"][] = $subgroupData;
                    }
                }
            }

            $data["goals"][] = $goalData;
        }

        return $data;
    }
    public function rate(Request $request, $groupClassId)
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

        $classGroup = Professor::findOrFail($groupClassId);

        $existingRating = $user->ratings()
            ->where('ratable_id', $classGroup->id)
            ->where('ratable_type', Professor::class)
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

    public function info22($reservationId)
    {
        $p = PrivetClassReservation::find($reservationId);
        $book = PrivetClassBook::where('privet_class_id', '=', $reservationId)->value('book_learning_id');

        $books = BookLearning::where('id', $book)->get();
        $discount = DiscountClassPrice::where('professor_id', $p->professor->id)
            ->where('class_mode', 'private')
            ->value('discount_percentage');

        $classPrice = ClassPrice::where('professor_id', $p->professor->id)
            ->where('language_id', $p->language->id)
            ->where('class_type', 'private') // چون کلاس گروهی است
            ->first();

        $base_price = ClassPrice::where('professor_id', $p->professor->id)
            ->where('language_id', $p->language->id)
            ->where('class_type', 'private') // چون کلاس گروهی است
            ->value('base_price');
        if (!$classPrice) {
            return api_response(null, 'شهریه ای برای کلاس  ثبت نشده است', 404);
        }
        $priceForLevel = match ((int)$p->languageLevel->id) {
            1 => $classPrice->beginner_price,
            2 => $classPrice->intermediate_price,
            3 => $classPrice->advanced_price,
            default => null,
        };

        $priceForAge = match ((int)$p->ageGroup->id) {
            1 => $classPrice->child_price,
            2 => $classPrice->teen_price,
            3 => $classPrice->adult_price,
            default => null,
        };

        $priceForPlace = match ((int)$p->teachingType->id) {
            2 => $classPrice->online_price ?? 0,
            1 => $classPrice->intermediate_price ?? 0,
            default => 0,
        };

        $total = $priceForAge + $priceForLevel + $priceForPlace;
        $total_price = $total + $base_price;

        $return = [
            'session_count' => $p->session_count,
            'language' => $p->language->title,
            'flag' => $p->language->icon,
            'skill' => optional($p->skillRelation)->name,
            'ageGroup' => $p->ageGroup->title,
            'languageLevel' => $p->languageLevel->title,
            'teachingType' => $p->teachingType->title,
            'platform' => $p->platform ?? null,
            'subject' => ($p->subject?->goal?->title ?? '') .
                ' (' . ($p->subject?->title ?? '') . ')',
            'books' => $books->map(fn($book) => [
                'id' => $book->id,
                'title' => $book->title,
                'language_id' => $book->language?->title,
                'flag' => $book->language?->icon,
                'subject' => $book->subgoal?->title,
                'age_group' => $book->ageGroup?->title,
                'language_level' => $book->languageLevel?->title,
                'images' => $book->product?->images ?? null,
            ]),
            'text' => $p->massage,
            'discount' => $p->discount_code,
            'professor_name' => $p->professor->full_name(),
            'professor_id' => $p->professor->id,
            'book_type' => $p->book_type,
            'address_type' => $p->address_type,
            'evidence' => $p->evidence? api::api.$p->evidence:null,
            'latitude' => ($p->address_id && $p->Offline) ? $p->Offline->latitude : $p->latitude,
            'longitude' => ($p->address_id && $p->Offline) ? $p->Offline->longitude : $p->longitude,

            'professor_profile' => $p->professor->profile ? api::api . $p->professor->profile : null,
            'price' => $total_price,
            'discount_price' => $discount ?? null,
            'total' => (int) $p->price,

        ];
        return api_response($return, 'ok');

    }


    public function reserve(Request $request1, $id)
    {
        $professor_id = $id;
        $student_id = auth()->user()->student->id;
        $beforeDash = Str::beforeLast($request1->address_id, '-');
        $afterDash = Str::afterLast($request1->address_id, '-');

        $addressId = (int)$afterDash;

        $request1->merge([
            'address_id' => $addressId,
            'address_type' => $beforeDash // اگر خواستی مقدار قبل از - رو هم استفاده کنی
        ]);
        $request = $request1->validate([
            'teaching_types' => 'required',
            'language' => 'required',
            'ageGroup' => 'required',
            'level' => 'required',
            'subject_id' => 'required',
            'address_id' => 'nullable',
            'address_type' => 'nullable',
            'platform' => 'nullable',
            'text' => 'nullable',
            'skill' => 'nullable',
            'session_count' => 'nullable',
            'discount_code' => 'nullable',
            'evidence' => 'nullable',
            'class_type' => 'required',
            'offer' => 'nullable',
            'books' => 'sometimes|array',
            'books.*' => 'exists:book_learning,id',
            'times' => 'required|array',
            'times.*' => 'exists:teacher_time_slots,id',
            'address'=>'nullable',
            'mobile'=>'nullable',
            'book_type'=>'nullable',
            'phone'=>'nullable',
            'latitude'=>'nullable',
            'longitude'=>'nullable',
        ]);

        $timeSlotIds = $request1->input('times');
        $timesCount = count($request1->input('times', []));
        if ($request1->teaching_types === 1) {
            $error = $this->validateConsecutiveTimeSlots($timeSlotIds);
            if ($error) {
                return api_response(null, $error);
            }
        }

        if ($request1->class_type == 'affordable')
        {
            if ($request1->offer >= $timesCount){
                return api_response([], 'تعداد جلسات حداقل باید ' . $request1->offer . ' باشد');
        }
        }





        if ($request['teaching_types'] === 2) {
            $request['address_id'] = null;
        } else {
            $request['platform'] = null;
        }
        $evidencePath = null;

        if ($request1->hasFile('evidence')) {
            $file = $request1->file('evidence');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/evidences'), $fileName);
            $evidencePath = 'uploads/evidences/' . $fileName;
        }


        $R = PrivetClassReservation::create([
            'professor_id' => $professor_id,
            'student_id' => $student_id,
            'teaching_type_id' => $request1->input('teaching_types'),
            'affordable_type' => $request1->input('offer'),
            'language_id' => $request1->input('language'),
            'age_group_id' => $request1->input('ageGroup'),
            'language_level_id' => $request1->input('level'),
            'subject_id' => $request1->input('subject_id'),
            'address_id' => $request1->input('address_id') ?? null,
            'platform' => $request1->input('platform') ?? null,
            'massage' => $request1->input('text') ?? null,
            'skill_id' => $request1->input('skill') ?? null,
            'discount_code' => $request1->input('discount_code') ?? null,
            'class_type' => $request1->input('class_type'),
            'landline' => $request1->input('phone')??null,
            'address' => $request1->input('address')??null,
            'mobile' => $request1->input('mobile')??null,
            'longitude' => $request1->input('longitude')??null,
            'latitude' => $request1->input('latitude')??null,
            'book_type' => $request1->input('book_type')??null,
            'status' => 'pending',
            'evidence' => $evidencePath, // اینجا مسیر فایل ذخیره میشه
            'session_count' => $timesCount ,
            'address_type' => $request1->input('address_type') ?? null,
        ]);
        $price = $this->calculatePrivetClassPrice($R->id, $R->session_count);
        $R->update([
            'price' => $price
        ]);

        if (!empty($request1['books'])) {
            foreach ($request1['books'] as $bookId) {
                PrivetClassBook::create([
                    'privet_class_id' => $R->id,
                    'book_learning_id' => $bookId,
                ]);
            }
        }
//        elseif (!empty($data['evidence'])) {
//            PrivetClassReservation::update([
//                'id' => $R->id,
//                'book_learning_id' => null, // مقدار `null` چون کتاب از `name` می‌آید
//                'name' => $data['name'], // فیلدی برای ذخیره نام کتاب
//            ]);
//        }

        $a = $this->reserveTimeSlots($R->id, $timeSlotIds, $R->teaching_type_id);
        return api_response($R->id, 'ثبت شد');

    }


    public function reserveTimeSlots($reservationId, array $timeSlotIds, $type)
    {
        $reservedSlots = PrivetTeacherTimeSlot::whereIn('teacher_time_slot_id', $timeSlotIds)->get();

        $expiredSlots = [];

        foreach ($reservedSlots as $reservedSlot) {
            $reservation = $reservedSlot->reservation;

            if (
                $reservation &&
                $reservation->status === 'pending' &&
                $reservation->created_at->lt(now()->subMinutes(10))
            ) {
                $expiredSlots[] = $reservedSlot->id;
            }
        }

        if (!empty($expiredSlots)) {
            PrivetTeacherTimeSlot::whereIn('id', $expiredSlots)->delete();
        }

        $stillReserved = PrivetTeacherTimeSlot::whereIn('teacher_time_slot_id', $timeSlotIds)->exists();

        if ($stillReserved) {
            return api_response(null, 'Some of the selected time slots are already reserved.');
        }

        // ذخیره اسلات‌ها برای این رزرو
        $data = collect($timeSlotIds)->map(function ($slotId) use ($reservationId) {
            return [
                'privet_class_reservation_id' => $reservationId,
                'teacher_time_slot_id' => $slotId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        });

        PrivetTeacherTimeSlot::insert($data->toArray());

        // آپدیت وضعیت اسلات‌ها به reserved
        TeacherTimeSlot::whereIn('id', $timeSlotIds)->update(['status' => 'reserved']);

        return api_response(null, 'Time slots reserved successfully.');
    }


    public function validateConsecutiveTimeSlots(array $timeSlotIds): ?string
    {
        $timeSlots = TeacherTimeSlot::whereIn('id', $timeSlotIds)
            ->orderBy('time')
            ->get();

        if ($timeSlots->count() < 1) {
            return 'هیچ تایمی انتخاب نشده است.';
        }

        $minBlocks = $timeSlots->first()->min_blocks;

        if ($timeSlots->count() < $minBlocks) {
            return "برای این رزرو حداقل {$minBlocks} تایم پشت سر هم نیاز است.";
        }

        $times = $timeSlots->pluck('time')->map(function ($time) {
            return Carbon::createFromFormat('H:i:s', $time);
        })->values();

        // بررسی اینکه تمام تایم‌ها پشت سر هم و نیم‌ساعته هستند
        for ($i = 1; $i < $times->count(); $i++) {
            $expected = $times[$i - 1]->copy()->addMinutes(30);
            if (!$times[$i]->equalTo($expected)) {
                return "تایم‌های انتخاب شده باید پشت سر هم و نیم‌ساعته باشند.";
            }
        }

        return null; // همه‌چیز اوکیه
    }


    public function pay_private_reservation(Request $request,$reservationId)
    {
        $request->validate([
            'code' => 'nullable|string',
        ]);
        $reservation = PrivetClassReservation::where('id', $reservationId)
            ->where('student_id', auth()->user()->student->id)
            ->where('payment_status', 'pending')
            ->latest()
            ->first();
        $amount = $reservation->price;

        if($request->code && $request->code != null)
        {
            $discount = DiscountForStudent::where('discountable_type' , Professor::class)->where('code', $request->code)->first();
            if (!$discount) {
                return api_response([],'تخفیفی پیدا نشد');
            }
            if ($discount->expires_at < now() or $discount->limit <= 0)
            {
                return api_response([],'کد تخفیف منقضی شده ');
            }
            $percentage =$discount->percentage;
            $amount =$amount - ($amount * $percentage / 100);
        }

        $reservation->update([
            'discount_code' => $request->code,
            'price' => $amount,
        ]);


        $description = "پرداخت رزرو کلاس خصوصی " ;

        $paymentService = new zarinpal();
        $callback = 'https://api.lingomasters.ir/zarin_callback/private';
        return $paymentService->pay($reservation, $amount, $description, $callback);
    }


    public function zarin_callback(Request $request)
    {

        if (!$request->filled('Authority') || !$request->filled('Status'))
            return api_response([], 'خطا در پاسخ درگاه پرداخت', 403);

        $order_payment = Payment::where('authority', '=', $request->Authority)->first();


        if (blank($order_payment))
            return redirect('https://lingomasters.ir/profile/wallet');


        $amount = $order_payment->amount;
        $authority = $order_payment->authority;
        $response = zarinpal()
            ->merchantId(config('zarinpal.merchant_id'))
            ->amount($amount)
            ->verification()
            ->authority($authority)
            ->send();
        $redirect_url = 'https://lingomasters.ir/profile/wallet';

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $payable = $order_payment->payable;
            if ($payable instanceof PrivetClassReservation) {
                $payable->update([
                    'payment_status' => 'failed',
                ]);
                $walletId = $payable->student->user->wallet->id;

                Transaction::create([
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => "پرداخت ناموفق رزرو کلاس خصوصی " ,
                    'wallet_id' => $walletId,
                    'status' => 'rejected',
                ]);
            }


            return redirect($redirect_url);
        }
        $ref = $response->referenceId();


        $payable = $order_payment->payable;
        if ($payable instanceof PrivetClassReservation) {
            $payable->update([
                'payment_status' => 'paid',
            ]);
            $smsService = new SmsServiceServices();

            $n = $payable->student->mobile;
            $d =   $smsService->sendPatternSms(
                '19nl31h6r7vffdc',
                $n,
                [
                    'teacher_name' => $payable->professor->full_name(),
                    'student_name' => $payable->student->full_name(),
                    'class_type' =>  $payable->subject->title,
                    'link' => 'https://lingomasters.ir/',
                    'date' => $payable->teacherTimeSlots->first()?->date,
                    'time' => $payable->teacherTimeSlots->first()?->time,
                ]
            );
        }
        $walletId = $payable->student->user->wallet->id;
        Transaction::create([
            'type' => 'payment',
            'amount' => $amount,
            'description' => "پرداخت موفق رزرو کلاس خصوصی " ,
            'wallet_id' => $walletId,
            'status' => 'approved',
        ]);
        $order_payment->update([
            'ref_id' => $ref,
            'status' => 'paid',
        ]);


        return redirect($redirect_url);
    }


    public function calculatePrivetClassPrice($reservationId, $session)
    {
        $reservation = PrivetClassReservation::with(['ageGroup', 'languageLevel', 'professor', 'language'])->find($reservationId);

        if (!$reservation) {
            return api_response(null, 'رزرو کلاس پیدا نشد', 404);
        }

        $classPrice = ClassPrice::where('professor_id', $reservation->professor_id)
            ->where('language_id', $reservation->language_id)
            ->where('class_type', 'private')
            ->first();

        if (!$classPrice) {
            return api_response(null, 'شهریه‌ای برای کلاس خصوصی ثبت نشده است', 404);
        }

        $basePrice = $classPrice->base_price;

        $priceForLevel = match ($reservation->languageLevel->id ?? null) {
            1 => $classPrice->beginner_price,
            2 => $classPrice->intermediate_price,
            3 => $classPrice->advanced_price,
            default => null,
        };

        $priceForAge = match ($reservation->ageGroup->id ?? null) {
            1 => $classPrice->child_price,
            2 => $classPrice->teen_price,
            3 => $classPrice->adult_price,
            default => null,
        };

        $typePrice = match ($reservation->teachingType->id ?? null) {
            2 => $classPrice->online_price ?? 0,
            1 => $classPrice->in_person_price ?? 0,
            default => 0,
        };

        $rawTotal = $basePrice + $priceForLevel + $priceForAge + $typePrice;
        $m = DiscountClassPrice::where('class_mode', 'private')
            ->where('professor_id', $reservation->professor->id)
            ->first();
        $discountPercent = match ($reservation->affordable_type) {
            '10' => $m->discount_for_10 ?? 0,
            '15' => $m->discount_for_15 ?? 0,
            '20' => $m->discount_for_20 ?? 0,
            default => 0,
        };

        $discountAmount = ($rawTotal * $discountPercent) / 100;
        $finalPrice = ($rawTotal - $discountAmount) * $session;


        return $finalPrice;
    }

    public function calender(Request $request, $id)
    {
        $startDate = $request->input('start_day', now()->toDateString());

        $weekMap = [
            0 => 'یکشنبه',
            1 => 'دوشنبه',
            2 => 'سه‌شنبه',
            3 => 'چهارشنبه',
            4 => 'پنجشنبه',
            5 => 'جمعه',
            6 => 'شنبه',
        ];

        $daysRange = [];
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime($startDate . " +$i days"));
            $dayName = $weekMap[date('w', strtotime($date))];
            $daysRange[$date] = $dayName;
        }

        $schedules = PrivetTeacherTimeSlot::with('teacherTimeSlot')
            ->where('privet_class_reservation_id', $id)
            ->get();

        $formattedOutput = [];

        foreach ($schedules as $schedule) {
            $slot = $schedule->teacherTimeSlot;
            if (!$slot) continue;

            $date = $slot->date;
            $dayName = $daysRange[$date] ?? null;

            if ($dayName) {
                $formattedOutput[$dayName][] = [
                    'id' => $slot->id,
                    'time' => substr($slot->time, 0, 5), // فقط ساعت و دقیقه
                    'date' => $slot->date,
                ];
            }
        }

        return api_response($formattedOutput, 'ok');
    }

    public function pay_private_reservation_Wallet(Request $request,$reservationId)
    {

        $request->validate([
            'code' => 'nullable|string',
        ]);

        $wallet = auth()->user()->wallet;
        $user_id = auth()->user()->id;

        $reservation = PrivetClassReservation::where('id', $reservationId)
            ->where('student_id', auth()->user()->student->id)
            ->where('payment_status', 'pending')
            ->latest()
            ->first();
        if (!$reservation){
            return api_response(null, 'کلاس یافت نشد مجددا تلاش کنید', 404);
        }
        $amount = $reservation->price;


        if($request->code && $request->code != null)
        {
            $discount = DiscountForStudent::where('discountable_type' , Webinar::class)->where('code', $request->code)->first();
            if (!$discount) {
                return api_response([],'تخفیفی پیدا نشد');
            }
            if ($discount->expires_at < now() or $discount->limit <= 0)
            {
                return api_response([],'کد تخفیف منقضی شده ');
            }
            $percentage =$discount->percentage;
            $amount = $amount - ($amount * $percentage / 100);
        }


        $reservation->update([
            'discount_code' => $request->code,
            'price' => $amount,
        ]);


        $now = now()->format('His');
        $rawString = $reservationId . '_' . $user_id . '_' . $now;

        $authority = crc32($rawString); // خروجی عدد صحیح

        $description = "پرداخت رزرو کلاس خصوصی " ;

       $pa = $reservation->payment()->create([
            'user_id' => auth()->id(),
            'gateway_id' => 2,
            'amount' => $amount,
            'authority' =>$authority,
            'status' => 'pending',
            'description' => $description,
        ]);
       $ta =  Transaction::create([
            'type' => 'payment',
            'amount' => $amount,
            'description' => $description,
            'wallet_id' => $wallet->id,
            'status' => 'Pending',
        ]);
        if ($wallet->balance > $amount) {
            $wallet->balance -= $amount;
            $wallet->save();
            $ta->update([
                'status' => 'approved',
            ]);
            $pa->update([
                'status' => 'paid',
            ]);
            $reservation->update([
                'payment_status' =>'paid',
                'status' =>'reserved',
            ]);
            $smsService = new SmsServiceServices();

                $n = $reservation->student->mobile;
              $d =   $smsService->sendPatternSms(
                '19nl31h6r7vffdc',
                  $n,
                [
                    'teacher_name' => $reservation->professor->full_name(),
                    'student_name' => $reservation->student->full_name(),
                    'class_type' =>  $reservation->subject->title ?? '',
                    'link' => 'https://lingomasters.ir/',
                    'date' => $reservation->teacherTimeSlots->first()?->date,
                    'time' => $reservation->teacherTimeSlots->first()?->time,
                ]
            );
            return api_response([],'پرداخت انجام شد و کلاس برای شما با موفقیت رزرو شد');
        }
        else{
            $ta->update([
                'status' => 'rejected',
            ]);
            $pa->update([
                'status' => 'failed',
            ]);

            return api_response([],'موجودی شما برای پرداخت این کلاس کافی نمیباشد' , 400);
        }


    }

    public function reserve1(Request $request1, $id)
    {
        $professor_id = $id;
        $student_id = auth()->user()->student->id;
        $beforeDash = Str::beforeLast($request1->address_id, '-');
        $afterDash = Str::afterLast($request1->address_id, '-');

        $addressId = (int)$afterDash;

        $request1->merge([
            'address_id' => $addressId,
            'address_type' => $beforeDash // اگر خواستی مقدار قبل از - رو هم استفاده کنی
        ]);
        $request = $request1->validate([
            'teaching_types' => 'required',
            'language' => 'required',
            'ageGroup' => 'required',
            'level' => 'required',
            'address_id' => 'nullable',
            'address_type' => 'nullable',
            'platform' => 'nullable',
            'skill' => 'nullable',
            'text' => 'nullable',
            'session_count' => 'nullable',
            'discount_code' => 'nullable',
            'evidence' => 'nullable',
            'offer' => 'nullable',
            'books' => 'sometimes|array',
            'books.*' => 'exists:book_learning,id',
            'times' => 'required|array',
            'times.*' => 'exists:teacher_time_slots,id',
            'address'=>'nullable',
            'mobile'=>'nullable',
            'book_type'=>'nullable',
            'phone'=>'nullable',
            'latitude'=>'nullable',
            'longitude'=>'nullable',
        ]);

        $timeSlotIds = $request1->input('times');
        $timesCount = count($request1->input('times', []));
//        if ($request1->teaching_types === 1) {
//            $error = $this->validateConsecutiveTimeSlots($timeSlotIds);
//            if ($error) {
//                return api_response(null, $error);
//            }
//        }

        if ($request1->class_type == 'affordable')
        {
            if ($request1->offer >= $timesCount){
                return api_response([], 'تعداد جلسات حداقل باید ' . $request1->offer . ' باشد');
            }
        }





        if ($request['teaching_types'] === 2) {
            $request['address_id'] = null;
        } else {
            $request['platform'] = null;
        }
        $evidencePath = null;

        if ($request1->hasFile('evidence')) {
            $file = $request1->file('evidence');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/evidences'), $fileName);
            $evidencePath = 'uploads/evidences/' . $fileName;
        }


        $R = PrivetClassReservation::create([
            'professor_id' => $professor_id,
            'student_id' => $student_id,
            'teaching_type_id' => $request1->input('teaching_types'),
            'affordable_type' => $request1->input('offer'),
            'language_id' => $request1->input('language'),
            'age_group_id' => $request1->input('ageGroup'),
            'language_level_id' => $request1->input('level'),
            'skill_id' => $request1->input('skill'),
            'subject_id' => $request1->input('subject_id'),
            'address_id' => $request1->input('address_id') ?? null,
            'platform' => $request1->input('platform') ?? null,
            'massage' => $request1->input('text') ?? null,
//            'skill' => $request1->input('skill') ?? null,
            'discount_code' => $request1->input('discount_code') ?? null,
            'class_type' => 'level_assessment',
            'landline' => $request1->input('phone')??null,
            'address' => $request1->input('address')??null,
            'mobile' => $request1->input('mobile')??null,
            'longitude' => $request1->input('longitude')??null,
            'latitude' => $request1->input('latitude')??null,
            'book_type' => $request1->input('book_type')??null,
            'status' => 'pending',
            'evidence' => $evidencePath, // اینجا مسیر فایل ذخیره میشه
            'session_count' => $timesCount ,
            'address_type' => $request1->input('address_type') ?? null,
        ]);
        $price = $this->calculatePrivetClassPrice($R->id, $R->session_count);
        $R->update([
            'price' => $price
        ]);

        if (!empty($request1['books'])) {
            foreach ($request1['books'] as $bookId) {
                PrivetClassBook::create([
                    'privet_class_id' => $R->id,
                    'book_learning_id' => $bookId,
                ]);
            }
        }
//        elseif (!empty($data['evidence'])) {
//            PrivetClassReservation::update([
//                'id' => $R->id,
//                'book_learning_id' => null, // مقدار `null` چون کتاب از `name` می‌آید
//                'name' => $data['name'], // فیلدی برای ذخیره نام کتاب
//            ]);
//        }

        $this->reserveTimeSlots($R->id, $timeSlotIds, $R->teaching_type_id);
        return api_response($R->id, 'ثبت شد');

    }








}
