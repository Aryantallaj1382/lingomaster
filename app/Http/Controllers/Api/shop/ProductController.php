<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\order\Order;
use App\Models\ProductView;
use App\Models\Rating;
use App\Models\shop\Category;
use App\Models\shop\Product;
use App\Models\shop\Publication;
use App\Models\shop\Subjects;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{


    public function index(Request $request, $slug)
    {
        $query = Product::where('just_show' ,false)->exceptHelpProducts()->with(['images', 'category'])
            ->filterByRequest($request)
            ->sortByRequest($request);

        $products = $query->paginate(5);

        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $product->images->isNotEmpty() ? api::api  . $product->images->first()->path : null,
                'selected_seller' => $product->selected_seller,
                'category' => $product->category->title ?? 'نامشخص',
                'is_like' => $product->is_liked,
                'is_download' => $product->is_download,
            ];
        });

        return api_response($products);
    }


    public function index1(Request $request)
    {
        $query = Product::exceptHelpProducts()->with(['images', 'category'])
            ->filterByRequest($request)
            ->sortByRequest($request);

        $products = $query->paginate(5);

        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $product->images->isNotEmpty() ? api::api  . $product->images->first()->path : null,
                'selected_seller' => $product->selected_seller,
                'category' => $product->category->title ?? 'نامشخص',
                'is_like' => $product->is_liked,
                'is_download' => $product->is_download,
            ];
        });

        return api_response($products);
    }

    public function index_off(Request $request)
    {
        $query = Product::exceptHelpProducts()->whereHas('sellers', function ($q) {
            $q->whereColumn('discounted_price', '<', 'price');
        })
            ->with(['images', 'sellers' => function ($q) {
                $q->whereColumn('discounted_price', '<', 'price')
                    ->orderBy('discounted_price', 'asc');
            }])
            ->latest();

        if ($request->filled('language')) {
            $query->whereRelation('language', 'language_id', '=', $request->language);
        }
        if ($request->filled('subject')) {
            $query->whereRelation('subject', 'subject_id', '=', $request->subject);
        }
        if ($request->filled('category')) {
            $query->whereRelation('category', 'category_id', '=', $request->category);
        }
        if ($request->filled('age_group')) {
            $query->whereRelation('age_group', 'age_group_id', '=', $request->age_group);
        }

        if ($request->filled('min_price') && $request->filled('max_price')) {
            $query->whereHas('sellers', function ($q) use ($request) {
                $q->whereBetween('discounted_price', [$request->min_price, $request->max_price]);
            });
        } elseif ($request->filled('min_price')) {
            $query->whereHas('sellers', function ($q) use ($request) {
                $q->where('discounted_price', '>=', $request->min_price);
            });
        } elseif ($request->filled('max_price')) {
            $query->whereHas('sellers', function ($q) use ($request) {
                $q->where('discounted_price', '<=', $request->max_price);
            });
        }

        $products = $query->paginate(5);

        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'is_liked' => $product->is_liked,
                'image' => $product->images->isNotEmpty()
                    ? api::api  . $product->images->first()->path
                    : null,
                'selected_seller' => $product->selected_seller,
                'category' => $product->category?->title ?? 'نامشخص',
                'is_download' => $product->is_download,
            ];
        });

        return api_response($products);
    }

    public function show(Product $product)
    {
        $product->load([
            'images',
            'videos',
            'SampleImages',
            'language',
            'subject',
            'category',
            'age_group',
            'covers',
            'publication',
            'product_type',
            'sellers.seller'
        ]);
        $view = null ;
        if (Auth::check()) {
             ProductView::logView($product->id);
        }
        $product->increment('view');

        $sellers = $product->sellers;

        $mainSeller = $sellers->where('seller_id', 1)->first();

        $cheapestSeller = $sellers->sortBy(function ($item) {
            return $item->discounted_price ?? $item->price;
        })->first();

        $mostExpensiveSeller = $sellers->sortByDesc(function ($item) {
            return $item->discounted_price ?? $item->price;
        })->first();

        $s = $sellers->map(function ($item) {
            return [
                'seller_id' => $item->id,
                'id' => $item->seller_id,
                'name' => optional($item->seller)->name,
                'price' => $item->price,
                'stock' => $item->quantity,
                'discount_percentage' => $item->discount_percentage,
                'discounted_price' => $item->discounted_price ?? $item->price,
                'discount_expire_at' => $item->discount_expire_at,

            ];
        });

        $imagePath = $product->images->isNotEmpty() ? api::api  . $product->images->first()->path : null;
        $videoPath = $product->videos->isNotEmpty() ? api::api  . $product->videos->first()->path : null;
        $covers = $product->covers->isNotEmpty() ? api::api  . $product->covers->first()->path : null;

        $sampleImages = $product->SampleImages->map(function ($sampleImage) {
            return [
                'path' => api::api  . $sampleImage->path,
                'id' => $sampleImage->id,
            ];
        })->toArray();


        $selectedSeller = $mainSeller ?? $cheapestSeller;


        $transformProductItem = function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $product->images->isNotEmpty() ?api::api  . $product->images->first()->path : null,
                'selected_seller' => $product->selected_seller,
                'category' => $product->category->title ?? 'نامشخص',
                'is_like' => $product->is_liked,
                'is_download' => $product->is_download,
            ];
        };

        $similarByCategory = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['images', 'category'])
            ->latest()
            ->take(4)
            ->get()
            ->map($transformProductItem);

        $similarByGoal = Product::where('learning_subgoal_id', $product->learning_subgoal_id)
            ->where('id', '!=', $product->id)
            ->with(['images', 'category'])
            ->latest()
            ->take(4)
            ->get()
            ->map($transformProductItem);

        $similarByPublication = Product::where('publication_id', $product->publication_id)
            ->where('id', '!=', $product->id)
            ->with(['images', 'category'])
            ->latest()
            ->take(4)
            ->get()
            ->map($transformProductItem);

        $similarByAuthor = Product::where('author','like', '%'.$product->author.'%')
            ->where('id', '!=', $product->id)
            ->with(['images', 'category'])
            ->latest()
            ->take(4)
            ->get()
            ->map($transformProductItem);
        if ($product->category->id == 33)
        {
            $r = 'دسته دوم';
        }
        else{$r = 'نو';}
        return api_response([
            'id' => $product->id,
            'title' => $product->title,
            'author' => $product->author,
            'Volume_number' => $product->Volume_number,
            'page_number' => $product->page_number,
            'language' => $product->language->title ?? null,
            'flag' => $product->language?->flag ? api::api.$product->language->flag :null,
            'age_group' => $product->age_group->title ?? null,

            'subject' => $product->goal->title ?? null,
            'category' => $product->category->title ?? null,
            'rade'=> $r ,

            'goal' => $product->goal->title ?? null,
            'year_of_publication' => $product->year_of_publication,
            'product_type' => $product->product_type->title ?? null,
            'Time_to_print' => $product->Time_to_print,
            'Dimensions' => $product->Dimensions,
            'publication' => $product->publication->title ?? null,
            'Paper_type' => $product->Paper_type,
            'Cover_type' => $product->Cover_type,
            'shabak_number' => $product->shabak_number,
            'fipa_number' => $product->fipa_number,
            'tags' => $product->tags,
            'weight' => $product->weight,
            'price' => $selectedSeller?->price,
            'off_price' => $selectedSeller?->discounted_price ?? $selectedSeller?->price,
            'is_like' => $product->is_liked,
            'category_slug' => $product->category->slug ?? null,
            'discount_percentage' => $selectedSeller?->discount_percentage,
            'discount_expiration' => $selectedSeller?->discount_expire_at,
            'image' => $imagePath,
            'video' => $videoPath,
            'video_covers' => $covers,
            'book_category' => $product->book_category,
            'audio_file' => $product->audio_file,
            'video_file' => $product->video_file,
            'video_accent' => $product->video_accent,
            'audio_accent' => $product->audio_accent,
            'sample_images' => $sampleImages,
            'is_download' => $product->is_download,
            'sellers' => $s,
            'selected_seller' => $product->selected_seller,
            'min_price' => $cheapestSeller?->discounted_price ?? $cheapestSeller?->price,
            'max_price' => $mostExpensiveSeller?->discounted_price ?? $mostExpensiveSeller?->price,
            'average_rate' => $product->average_rating,
            'rate_count' => $product->ratings_count,
            'rate' => $product->user_rating,
            'accent' => $product->accent,
            'similar_by_category' => $similarByCategory,
            'similar_by_goal' => $similarByGoal,
            'similar_by_publication' => $similarByPublication,
            'similar_by_author' => $similarByAuthor,

        ]);
    }
    public function sellerProducts(Request $request, $seller_id)
    {
        $query = Product::with(['images', 'category'])
            ->whereHas('sellers', function ($q) use ($seller_id) {
                $q->where('seller_id', $seller_id);
            })
            ->filterByRequest($request)
            ->sortByRequest($request);

        $products = $query->paginate(12);

        $products->getCollection()->transform(function ($product) use ($seller_id){
            return [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $product->first_image,
                'selected_seller' => $product->getSelectedSellerBy($seller_id),
                'category' => $product->category->title ?? 'نامشخص',
                'is_herper' => $product->category_id == 1 ? 1 : 0,
                'is_like' => $product->is_liked,
                'is_download' => $product->is_download,
            ];
        });

        return api_response($products);
    }

    public function GetFilter($slug)
    {
        $category = Category::where('slug', $slug)->first();

        // حذف دسته‌بندی با آیدی 1
        $category1 = Category::where('id', '!=', 1)->get();
        if (!$category) {
            return api_response(null, 'محصولی با این اسلاگ یافت نشد', 200);
        }

        // دریافت آیدی همه محصولات این دسته‌بندی
        $productIds = Product::where('category_id', $category->id)->pluck('id');

        // محاسبه کمترین و بیشترین قیمت با توجه به جدول product_seller
        $minPrice = \DB::table('product_seller')
            ->whereIn('product_id', $productIds)
            ->selectRaw('MIN(COALESCE(discounted_price, price)) as min_price')
            ->value('min_price');

        $maxPrice = \DB::table('product_seller')
            ->whereIn('product_id', $productIds)
            ->selectRaw('MAX(COALESCE(discounted_price, price)) as max_price')
            ->value('max_price');

        // بقیه فیلترها
        $products = Product::whereIn('id', $productIds);
        $publicationIds = $products->pluck('publication_id')->unique();
        $ageGroupIds = $products->pluck('age_group_id')->unique();
        $subjectIds = $products->pluck('subject_id')->unique();
        $languageIds = $products->pluck('language_id')->unique();

        $publications = Publication::whereIn('id', $publicationIds)->get();
        $ageGroups = AgeGroup::whereIn('id', $ageGroupIds)->get();
        $subjects = Subjects::whereIn('id', $subjectIds)->get();
        $languages = Languages::whereIn('id', $languageIds)->get();

        $result = [
            'category' => $category1->map(fn($cat) => [
                'value' => $cat->id,
                'name' => $cat->title,
                'slug' => $cat->slug,
            ])->toArray(),
            'publication' => $publications->map(fn($publication) => [
                'value' => $publication->id,
                'name' => $publication->title,
            ])->toArray(),
            'age_group' => $ageGroups->map(fn($ageGroup) => [
                'value' => $ageGroup->id,
                'name' => $ageGroup->title,
            ])->toArray(),
            'subject' => $subjects->map(fn($subject) => [
                'value' => $subject->id,
                'name' => $subject->title,
            ])->toArray(),
            'language' => $languages->map(fn($language) => [
                'value' => $language->id,
                'name' => $language->title,
            ])->toArray(),
            'price_range' => [
                'min_price' => (int)$minPrice ?? 0,
                'max_price' => (int)$maxPrice ?? 0,
            ],
        ];

        return api_response($result, 'فیلتر با موفقیت ارسال شد');
    }

    public function GetFilter1()
    {
        $category1 = Category::where('id', '!=', 1)->get();

        $productIds = Product::pluck('id')->toArray();
        $minPrice = \DB::table('product_seller')
            ->whereIn('product_id', $productIds)
            ->selectRaw('MIN(COALESCE(discounted_price, price)) as min_price')
            ->value('min_price');

        $maxPrice = \DB::table('product_seller')
            ->whereIn('product_id', $productIds)
            ->selectRaw('MAX(COALESCE(discounted_price, price)) as max_price')
            ->value('max_price');

        $products = Product::all();
        $publicationIds = $products->pluck('publication_id')->unique();
        $ageGroupIds = $products->pluck('age_group_id')->unique();
        $subjectIds = $products->pluck('subject_id')->unique();
        $languageIds = $products->pluck('language_id')->unique();

        $publications = Publication::whereIn('id', $publicationIds)->get();
        $ageGroups = AgeGroup::whereIn('id', $ageGroupIds)->get();
        $subjects = Subjects::whereIn('id', $subjectIds)->get();
        $languages = Languages::whereIn('id', $languageIds)->get();

        $result = [
            'category' => $category1->map(fn($cat) => [
                'value' => $cat->id,
                'name' => $cat->title,
                'slug' => $cat->slug,
            ])->toArray(),
            'publication' => $publications->map(fn($publication) => [
                'value' => $publication->id,
                'name' => $publication->title,
            ])->toArray(),
            'age_group' => $ageGroups->map(fn($ageGroup) => [
                'value' => $ageGroup->id,
                'name' => $ageGroup->title,
            ])->toArray(),
            'subject' => $subjects->map(fn($subject) => [
                'value' => $subject->id,
                'name' => $subject->title,
            ])->toArray(),
            'language' => $languages->map(fn($language) => [
                'value' => $language->id,
                'name' => $language->title,
            ])->toArray(),
            'price_range' => [
                'min_price' => (int)$minPrice ?? 0,
                'max_price' => (int)$maxPrice ?? 0,
            ],
        ];

        return api_response($result, 'فیلتر با موفقیت ارسال شد');
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

        $classGroup = Product::findOrFail($groupClassId);

        $existingRating = $user->ratings()
            ->where('ratable_id', $classGroup->id)
            ->where('ratable_type', Product::class)
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

    public function comment(Request $request, $id)
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


        $groupClass = Product::findOrFail($id);

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

        $groupClass = Product::findOrFail($id);


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

    public function toggleLike(Request $request, $id)
    {
        $group = Product::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return api_response([], 'برای لایک کردن لاگین کنید', 401);
        }

        $like = $user->likes()
            ->where('likeable_id', $group->id)
            ->where('likeable_type', Product::class)
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

    public function seller_info($id)
    {
        $orders = Order::where('seller_id', $id)
            ->whereNotNull('product_satisfaction')
            ->whereNotNull('delivery_rating')
            ->get();

        if ($orders->isEmpty()) {
            return api_response([
                'status' => false,
                'message' => 'هیچ سفارشی با امتیاز برای این فروشنده یافت نشد.',
            ]);
        }

        $productSatisfactionAvg = $orders->avg('product_satisfaction');
        $deliveryRatingAvg = $orders->avg('delivery_rating');

        // محاسبه درصد مرجوع نشده
        $totalOrders = Order::where('seller_id', $id)->count();
        $notReturnedOrders = Order::where('seller_id', $id)
            ->where(function ($query) {
                $query->whereNull('is_returned')->orWhere('is_returned', '!=', 1);
            })
            ->count();

        $returnRate = $totalOrders > 0 ? round(($notReturnedOrders / $totalOrders) * 100, 1) : null;

        $firstOrder = $orders->first();
        $sellerName = optional($firstOrder->seller)->name ?? 'نامشخص';

        return api_response([
            'satisfaction' => round($productSatisfactionAvg * 10, 1), // درصد
            'rating' => round($deliveryRatingAvg * 10, 1), // درصد
            'not_returned_percent' => $returnRate, // درصد مرجوع نشده
            'number' =>$notReturnedOrders,
            'seller' => $sellerName,
        ]);
    }


    public function q_search(Request $request)
    {
        $query = $request->query('search');
        $results = Product::where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhereHas('category', fn ($q2) =>
                $q2->where('title', 'like', "%{$query}%")
                );
        })
            // سایر فیلترها مثلاً نوع وبینار
            ->when($request->filled('type'), fn ($q) =>
            $q->where('type', $request->type)
            )
            ->with('category:id,title')
            ->take(10)
            ->get()
            ->map(fn ($w) => [
                'id'       => $w->id,
                'title'    => $w->title,
                'category' => $w->category->title ?? null,
                'is_help'=> ($w->category?->id ?? 0) == 1,

            ])

            ->values()
            ->toBase();

        return api_response($results);
    }

    public function search(Request $request)
    {
        $query = $request->query('search');
        $results = Product::where(function ($q) use ($query) {
            $q->where('title', 'like', "%{$query}%")
                ->orWhereHas('category', fn ($q2) =>
                $q2->where('title', 'like', "%{$query}%")
                );
        })
            ->with('category:id,title')
            ->take(10)
            ->get()
            ->map(fn ($product) => [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $product->images->isNotEmpty() ? api::api  . $product->images->first()->path : null,
                'selected_seller' => $product->selected_seller,
                'category' => $product->category->title ?? 'نامشخص',
                'is_like' => $product->is_liked,
                'is_download' => $product->is_download,
                'is_help'=> ($product->category?->id ?? 0) == 1,

            ])

            ->values()
            ->toBase();

        return api_response($results);
    }


}
