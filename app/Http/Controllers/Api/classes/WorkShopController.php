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
use App\Models\Professors\BookLearning;
use App\Models\Professors\DiscountForWebinars;
use App\Models\Professors\DiscountForWorkShop;
use App\Models\Professors\DiscountType;
use App\Models\Professors\ExperimentDiscountType;
use App\Models\Professors\Gender;
use App\Models\Professors\GroupClass;
use App\Models\Professors\GroupClassBook;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningSubgoal;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Professor;
use App\Models\Professors\ProfessorAuthor;
use App\Models\Professors\ProfessorBlog;
use App\Models\Professors\ProfessorCertificate;
use App\Models\Professors\ProfessorEducation;
use App\Models\Professors\ProfessorHonor;
use App\Models\Professors\ProfessorRecord;
use App\Models\Professors\ProfessorTranslation1;
use App\Models\Professors\Webinar;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\webinarsBook;
use App\Models\Professors\WorkShop;
use App\Models\Professors\WorkShopReservation;
use App\Models\Rating;
use App\Models\shop\Accent;
use App\Models\shop\Comment;
use App\Models\TeachingType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class WorkShopController extends Controller
{
    public function getFilters(Request $request)
    {
        $languages = Languages::select('id', 'title')->get();
        $teachingTypes = TeachingType::select('id', 'title')->get();
        $ageGroups = AgeGroup::select('id', 'title')->get();
        $languageLevels = LanguageLevel::select('id', 'title')->get();
        $genders = Gender::select('id', 'title')->get();
        $accent = Accent::select('id', 'title')->get();
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

        $minPrice = (int) $professor->min('price');
        $maxPrice = (int) $professor->max('price');

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
            'assessmentDiscountTypes' => $discountTypes->toArray(),
            'experimentDiscountTypes' => $experimentDiscountTypes->toArray(),
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
                        ])->unique('id')->values(),
                    ];
                })
                ->values()
                ->toArray(),


        ];

        return api_response($result, 'فیلتر با موفقیت ارسال شد');
    }

    public function index(Request $request)
    {
        $webinar = WorkShop::with('professor', 'language.accents', 'level', 'discount')->where('admin_status', 'approved');


        if ($request->filled('search')) {
            $webinar->whereRelation('subject', 'title', 'like','%' . $request->query('search') . '%');
        }
        if ($request->filled('professor')) {
            $webinar->whereRelation('professor', 'first_name', 'like','%' . $request->query('professor') . '%');
        }
        if ($request->filled('city')) {
            $webinar->whereRelation('address', 'city_id', '=', $request->city);
        }

        if ($request->filled('country')) {
            $webinar->whereRelation('address', 'country_id', '=', $request->country);
        }
        if ($request->filled('professor')) {
            $webinar->whereRelation('professor', 'first_name', 'like','%' . $request->query('professor') . '%');
        }
        if ($request->filled('language')) {
            $webinar->whereRelation('language', 'id', '=', $request->query('language'));
        }
        if ($request->filled('teachingTypes')) {
            $webinar->whereRelation('teachingType', 'id', '=', $request->teachingTypes);
        }
        if ($request->filled('accent')) {
            $webinar->whereRelation('language.accents', 'id', '=', $request->accent);
        }
        if ($request->filled('subject_id')) {
            $webinar->whereRelation('subject', 'id', '=', $request->subject_id);
        }
        if ($request->filled('languageLevels')) {
            $webinar->whereRelation('level', 'id', '=', $request->languageLevels);
        }
        if ($request->filled('age_group')) {
            $webinar->whereRelation('ageGroup', 'id', '=', $request->age_group);
        }
        if ($request->filled('genders')) {
            $webinar->whereRelation('gender', 'id', '=', $request->genders);
        }
        if ($request->filled('closest_time')) {
            $webinar->where('start_date', '>=', now())
                ->orderBy('start_date');
        }
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $webinar->whereBetween('total_price', [$request->min_price, $request->max_price]);
        }
        elseif ($request->filled('min_price')) {
            $webinar->where('total_price', '>=', $request->min_price);
        }
        elseif ($request->filled('max_price')) {
            $webinar->where('total_price', '<=', $request->max_price);
        }
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'best_selling':
                    $webinar->orderBy('students_count', 'desc');
                    break;
                case 'newest':
                    $webinar->orderBy('created_at', 'desc');
                case 'discounted':
                    $webinar->whereHas('discount', function ($q) {
                        $q->where('discount_percentage', '>', 0);
                    })
                        ->with('discount')
                        ->orderByDesc(
                            DiscountForWorkShop::select('discount_percentage')
                                ->whereColumn('discounts_for_work_shop.workshop_id', 'work_shops.id')  // 👈 اصلاح شده
                                ->limit(1)
                        );
                    break;

                case 'most_viewed':
                    $webinar->orderBy('view', 'desc');
                    break;
                case 'cheap':
                    $webinar->orderBy('new_total_price', 'asc');
                    break;
                case 'expensive':
                    $webinar->orderBy('new_total_price', 'desc');
                    break;
            }
        }
        $is_like = false;
        $rate = 0;
        if( auth()->user())
        {
            $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $webinar->id)->where('likeable_type', GroupClass::class)->exists();
            $rate = Rating::where('user_id', auth()->user()->id)->where('ratable_id', $webinar->id)->where('ratable_type', GroupClass::class)->first();

            if ($rate) {
                $rating = $rate->rating; // نمره‌ای که کاربر داده است
            } else {
                $rating = 0; // اگر کاربر نمره‌ای نداده باشد، مقدار پیش‌فرض را تعیین کنید
            }
        }

        $webinars = $webinar->latest()->paginate(12);

        $webinars->getCollection()->transform(function ($webinar) {

            $isLiked = auth()->user() && $webinar->isLikedByUser(auth()->user()->id);
            $p = $webinar->discount->discount_percentage ?? null;
            $t = $webinar->professor->discount->last()?->discount_percentage ?? null;
            $numberOfRatings = $webinar->ratings()->count();
            $averageRating = $webinar->ratings()->avg('rating');
            $averageRating = round($averageRating, 1);            return [
                'id' => $webinar->id,
                'title' => $webinar->subject->title,
                'professor' => $webinar->professor->full_name(),
                'image' => $webinar->image ?? null,
                'language' => $webinar->language->title,
                'flag' => api::api.$webinar->language->flag,
                'price' => $webinar->total_price,
                'total_price' => $webinar->new_total_price,
                'discount' => $webinar->total_percentage ?? null,
                'price_discount' => $webinar->new_total_price,
                'date' => $webinar->date ?? null,
                'time' => $webinar->hour,
                'is_like' => Like::where('user_id', auth()->id())->where('likeable_id', $webinar->id)->where('likeable_type', WorkShop::class)->exists(),
                'max_capacity' => $webinar->max_capacity,
                'rate' => $rating ?? null,
                'rate_count' => $numberOfRatings ?? 0,
                'rate_av' => $averageRating ??0,
            ];
        });
        return api_response($webinars, 'ok');
    }

    public function show($id)
    {
        $webinar = WorkShop::with('professor', 'language.accents', 'level')->findOrFail($id);
        $is_like = false;
        $rate = 0;
        if( auth()->user())
        {
            $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $webinar->id)->where('likeable_type', GroupClass::class)->exists();
            $rate = Rating::where('user_id', auth()->user()->id)->where('ratable_id', $webinar->id)->where('ratable_type', GroupClass::class)->first();

            if ($rate) {
                $rating = $rate->rating;
            } else {
                $rating = 0;
            }
        }

        $book = webinarsBook::where('webinar_id', '=', $id)->value('book_learning_id');
        $books = BookLearning::where('id', $book)->get();
        $related = WorkShop::where('subject_id', $webinar->subject_id)
            ->where('id', '!=', $id)
            ->with('professor', 'language.accents', 'level')
            ->take(4)->get()->map(function ($class) {
                $is_like =null;
                if( auth()->user())
                {
                    $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $class->id)->where('likeable_type', GroupClass::class)->exists();
                }



                return [
                    'id' => $class->id,
                    'title' => $class->subject->title,
                    'flag' => $class->language->flag,
                    'professor' => $class->professor->full_name(),
                    'image' => $class->image ?? null,
                    'language' => $class->language->title,
                    'price' => $class->total_price,
                    'discount' => $class->new_total_price ?? null,
                    'price_discount' =>$class->total_price,
                    'date' =>$class->date,
                    'time' =>$class->hour,
                    'is_like' => $is_like ,

                ];
            });


        $e = WorkShop::where('id', '!=', $id)
            ->where('professors_id' ,$webinar->professors_id )
            ->where('date', '<' , now())
            ->with('professor', 'language.accents', 'level')
            ->take(4)->get()->map(function ($class) {
                $isLiked = auth()->user() && $class->isLikedByUser(auth()->user()->id);
                $is_like =null;
                if( auth()->user())
                {
                    $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $class->id)->where('likeable_type', GroupClass::class)->exists();
                }

                return [
                    'id' => $class->id,
                    'title' => $class->subject->title,
                    'flag' => $class->language->flag,
                    'professor' => $class->professor->full_name(),
                    'image' => $class->image ?? null,
                    'language' => $class->language->title,
                    'price' => $class->total_price,
                    'max_capacity' => $class->max_capacity,
                    'discount' => $class->new_total_price ?? null,
                    'price_discount' =>$class->total_price,
                    'date' =>$class->date,
                    'time' =>$class->hour,
                    'is_like' => $is_like ,
                    'is_over'=>1
                ];
            });

        $e = WorkShop::where('id', '!=', $id)
            ->where('professors_id' ,$webinar->professors_id )
            ->where('date', '<' , now())
            ->with('professor', 'language.accents', 'level')
            ->take(4)->get()->map(function ($class) {
                $isLiked = auth()->user() && $class->isLikedByUser(auth()->user()->id);
                $is_like =null;
                if( auth()->user())
                {
                    $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $class->id)->where('likeable_type', GroupClass::class)->exists();
                }

                return [
                    'id' => $class->id,
                    'title' => $class->subject->title,
                    'flag' => $class->language->flag,
                    'professor' => $class->professor->full_name(),
                    'image' => $class->image ?? null,
                    'language' => $class->language->title,
                    'max_capacity' => $class->max_capacity,
                    'price' => $class->total_price,
                    'discount' => $class->new_total_price ?? null,
                    'price_discount' =>$class->total_price,
                    'date' =>$class->date,
                    'time' =>$class->hour,
                    'is_like' => $is_like ,
                    'is_over'=>1
                ];
            });



        $discount = $webinar->discount ?? null;
        if ($webinar->discount && $webinar->discount->end_date < now()) {
            if ($discount->discount_type === 'hourly'){
                $webinar->discount->delete();

            }
        }
        if ($webinar->discount) {
            if ($discount->discount_type === 'hourly') {
                if ($discount->start_date < now() || $discount->end_date > now()) {
                    $discount_type = 2;
                    $end_date = Carbon::parse($discount->end_date)->format('Y-m-d H:i:s');
                    $percentage = $discount->percentage;

                }
            } else if ($discount->discount_type === 'first_few') {
                $discount_type = 3;
                $count = $webinar->reserve()->where('payment_status', 'paid')->where('group_class_id',$id)->count();
                $all_count  = $discount->first_few_count;
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

        $numberOfRatings = $webinar->ratings()->count();
        $averageRating = $webinar->ratings()->avg('rating');
        $averageRating = round($averageRating, 1);
        $c = ($webinar->date > now() && $webinar->hour > now()) ? 1 : 0;
        return api_response([
            'id' => $webinar->id,
            'title' => $webinar->subject->title,
            'professor' => $webinar->professor->full_name(),
            'professor_profile' => $webinar->professor->profile ?? null,
            'image' => $webinar->image ?? null,
            'language' => $webinar->language->title,
            'flag' => $webinar->language->flag,
            'level' => $webinar->level->title ??null,
            'subject' => $webinar->subject->title ??null,
            'gender' => $webinar->gender->title ??null,
            'headline' => $webinar->headline,
            'longitude' => $webinar->longitude,
            'latitude' => $webinar->latitude,
            'video' => $webinar->video,
            'ageGroup' => $webinar->ageGroup->title,
            'professor_id' => $webinar->professor->id,
            'max_capacity' => $webinar->max_capacity,
            'مباحث دوره' => null,
            'city' => $webinar->city->name ?? null,
            'country' => $webinar->country->title?? null,
            'region' => $webinar->region?? null,
            'price' => $webinar->total_price,
            'discount' => $webinar->total_percentage ?? null,
            'price_discount' => $webinar->new_total_price ?? null,
            'start_date' => $webinar->date ?? null,
            'time' => $webinar->hour,
            'is_like' => $is_like,
            'rate' => $rating ?? null,
            'rate_count' => $numberOfRatings ?? 0,
            'rate_av' => $averageRating ??0,
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
            'last_workshop' => $e,
            'is_over' => $c ,


            'skills'=>[
                'blogs' => ProfessorBlog::where('professors_id', $webinar->professor->id)
                    ->where('language_id', $webinar->language->id)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                        'is_persian' => $blog->is_persian,
                    ])->groupBy('is_persian'),
                'honor' => ProfessorHonor::where('professors_id',$webinar->professor->id)->where('language_id', $webinar->language->id)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                    ]),

                'certificate' => ProfessorCertificate::where('professors_id',$webinar->professor->id)
                    ->where('language_id', $webinar->language->id)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                        'in_iran' =>$blog->in_iran
                    ])->groupBy('in_iran'),
                'translation' => ProfessorTranslation1::where('professors_id',$webinar->professor->id)->where('language_id', $webinar->language->id)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                    ]),
                'records' => ProfessorRecord::where('language_id',$webinar->language->id)
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
                'education' => ProfessorEducation::where('professors_id',$webinar->professor->id)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->latest_evidence,
                        'file' => api::api . $blog->latest_evidence_file_path,
                    ]),
                'author' => ProfessorAuthor::where('professors_id',$webinar->professor->id)
                    ->get()->map(fn($blog) => [
                        'name' => $blog->name,
                        'file' => api::api . $blog->file_path,
                    ]),
            ],

        ]);

    }

    public function toggleLike(Request $request, $id)
    {
        $group = WorkShop::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return api_response([], 'برای لایک کردن لاگین کنید', 401);
        }

        $like = $user->likes()
            ->where('likeable_id', $group->id)
            ->where('likeable_type', WorkShop::class)
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

        $groupClass = WorkShop::findOrFail($groupClassId);

        $videoUrl = null;
        $voiceUrl = null;

        if ($request->hasFile('video_url')) {
            $videoPath = $request->file('video_url')->store('comment/videos', 'public');
            $videoUrl = $videoPath;
        }
        if ($request->hasFile('voice_url')) {
            $voicePath = $request->file('voice_url')->store('comment/voices', 'public');
            $voiceUrl = $voicePath;
        }
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

        $groupClass = WorkShop::findOrFail($groupClassId);



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
        ],[],[
            'rating' =>'امتیاز'
        ]);

        $classGroup = WorkShop::findOrFail($groupClassId);

        $existingRating = $user->ratings()
            ->where('ratable_id', $classGroup->id)
            ->where('ratable_type', WorkShop::class)
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



    public function pay_group_reservation(Request $request,$reservationId)
    {
        $request->validate([
            'code' => 'nullable|string',
        ]);
        $webinar = WorkShop::findOrFail($reservationId);
        $price = $webinar->new_total_price;
        if($request->code)
        {
            $discount = DiscountForStudent::where('discountable_type' , WorkShop::class)->where('code', $request->code)->first();
            if (!$discount) {
                return api_response([],'تخفیفی پیدا نشد');
            }
            if ($discount->expires_at < now() or $discount->limit <= 0)
            {
                return api_response([],'کد تخفیف منقضی شده ');
            }
            $percentage =$discount->percentage;
            $price = $price - ($price * $percentage / 100);
        }

        $reservation = WorkShopReservation::firstOrCreate(
            [
                'work_shop_id'    => $reservationId,
                'student_id'    => auth()->user()->student->id,
                'payment_status'=> 'pending',
                'discount_code' => $request->code ?? null ,
                'final_price'=> $price,
            ]
        );


        $group = $reservation->WorkShop;

        $description = "پرداخت رزرو کلاس ورکشاپ: " . $group->subject->title;

        $paymentService = new zarinpal();
        $callback ='https://api.lingomasters.ir/zarin_callback_work';
        return $paymentService->pay($reservation, $price, $description , $callback);
    }

    public function zarin_callback_work(Request $request)
    {

        if (!$request->filled('Authority') || !$request->filled('Status'))
            return api_response([], 'خطا در پاسخ درگاه پرداخت', 403);

        $order_payment = Payment::where('authority', '=', $request->Authority)->first();


        if (blank($order_payment))
            return api_response([], 'اطلاعات پرداخت حذف شده است', 403);


        $amount =  $order_payment->amount;
        $authority = $order_payment->authority;
        $response = zarinpal()
            ->merchantId(config('zarinpal.merchant_id'))
            ->amount($amount)
            ->verification()
            ->authority($authority)
            ->send();
        $redirect_url = 'https://lingomasters.ir/workshop/success' ;

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $payable = $order_payment->payable;
            if ($payable instanceof WorkShopReservation) {
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
        $ref =  $response->referenceId();


        $payable = $order_payment->payable;
        if ($payable instanceof WorkShopReservation) {
            $payable->update([
                'payment_status' => 'paid',
            ]);
            $walletId = $payable->student->user->wallet->id;

            Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'description' => "پرداخت موفق رزرو کلاس خصوصی " ,
                'wallet_id' => $walletId,
                'status' => 'approved',
            ]);
        }

        $order_payment->update([
            'ref_id' =>$ref,
            'status' => 'paid',
        ]);



        return redirect($redirect_url);
    }


    public function info22($reservationId)
    {
        $group = WorkShop::with( 'reserve')->find($reservationId);


        $return = [
            'professor' => $group->professor->full_name(),
            'title' => $group->subject->title ?? null,
            'price' => $group->total_price?? null,
            'offPrice' => $group->new_total_price?? $group->total_price,
            'discount' => $group->total_percentage?? null,
            'id' => $group->id ,
            'image' => $group->image ,

        ];
        return api_response($return,'ok');

    }

    public function pay_private_reservation_Wallet(Request $request,$reservationId)
    {
        $request->validate([
            'code' => 'nullable|string',
        ]);
        $wallet = auth()->user()->wallet;
        $class = WorkShop::findOrFail($reservationId);
        $price = $class->new_total_price;

        if($request->code && $request->code != null)
        {
            $discount = DiscountForStudent::where('discountable_type' , WorkShop::class)->where('code', $request->code)->first();
            if (!$discount) {
                return api_response([],'تخفیفی پیدا نشد');
            }
            if ($discount->expires_at < now() or $discount->limit <= 0)
            {
                return api_response([],'کد تخفیف منقضی شده ');
            }
            $percentage =$discount->percentage;
            $price = $price - ($price * $percentage / 100);
        }



        $reservation = WorkShopReservation::firstOrCreate(
            [
                'work_shop_id'    => $reservationId,
                'student_id'    => auth()->user()->student->id,
                'payment_status'=> 'pending',
                'status'=> 'pending',
                'discount_code' => $request->code ?? null ,
                'final_price'=> $price,
            ]
        );
        if (!$reservation){
            return api_response(null, '<UNK> <UNK> <UNK> <UNK>', 404);
        }
        $is_paid = WorkShopReservation::where('work_shop_id', $reservationId)->where('student_id', auth()->user()->student->id)
            ->where('payment_status', 'paid')->exists();
        if ($is_paid){
            return api_response([] , 'شما قبلا این کلاس را رزرو کرده اید',401);
        }

        $description = "پرداخت رزرو ورکشاپ " ;
        $user_id = auth()->user()->id;

        $now = now()->format('His'); // ساعت به صورت 143251
        $rawString = $reservationId . '_' . $user_id . '_' . $now;

        $authority = crc32($rawString); // خروجی عدد صحیح

        $pa = $reservation->payment()->create([
            'user_id' => auth()->id(),
            'gateway_id' => 2,
            'amount' => $price,
            'authority' =>$authority,
            'status' => 'pending',
            'description' => $description,
        ]);
        $ta =  Transaction::create([
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
                'payment_status' =>'paid',
                'status' =>'reserved',
            ]);
            return api_response([],'پرداخت انجام شد و کلاس برای شما با موفقیت رزرو شد');

        }
        else{
            $ta->update([
                'status' => 'rejected',
            ]);
            $pa->update([
                'status' => 'failed',
            ]);

            return api_response([],'موجودی شما برای پرداخت این کلاس کافی نمیباشد', 400);
        }


    }




}
