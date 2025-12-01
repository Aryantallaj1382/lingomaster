<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\contacts\api;
use App\Models\Exam_question\Exam;
use App\Models\Like;
use App\Models\Professors\BookLearning;
use App\Models\Professors\GroupClass;
use App\Models\Professors\Professor;
use App\Models\Professors\Webinar;
use App\Models\Professors\WorkShop;
use App\Models\shop\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $allowedTypes = [
            'private' => Professor::class,
            'group' => GroupClass::class,
            'webinar' => Webinar::class,
            'workshop' => WorkShop::class,
            'book' => Product::class,
            'blog' => Blog::class,
            'exam' => Exam::class,
        ];

        $typeKey = $request->query('type');

        $query = Like::with('likeable')->where('user_id', $user->id);

        if ($typeKey && isset($allowedTypes[$typeKey])) {
            $query->where('likeable_type', $allowedTypes[$typeKey]);
        }

        $likes = $query->paginate();

        $likes->setCollection(
            $likes->getCollection()
                ->map(function ($like) {
                    $item = $like->likeable;
                    if (!$item) return null;

                    switch (get_class($item)) {

                case Professor::class:
                    $firstAddress = $item->first()?->addresses->first();
                    $cityName = optional($firstAddress?->city)->name;
                    $countryName = optional($firstAddress?->country)->title;
                    $discount = optional(
                        $item->ClassPrice->first()?->discount
                    )->discount_percentage;
                    return [
                        'type' => 'private_class',
                        'id' => $item->id,
                        'name' => $item->full_name() ?? '',
                        'views_count' => $item->views_count,
                        'language' => $item->languages->first()->title,
                        'flag' => $item->languages->first()->icon,
                        'teachingTypes' => $item->teachingTypes->first()->title,
                        'profile' => $item->profile ? api::api.$item->profile : null,
                        'price' => $item->ClassPrice()->where('class_type', 'private')->value('base_price'),
                        'discount' => $discount,
                        'rate' => $item->ratings()->avg('rating') ?? 0,
                        'city' => $cityName,
                        'country' => $countryName,
                    ];

                case GroupClass::class:
                    $discount = optional(
                        $item->discount->first()
                    )->discount_percentage;
                    return [
                        'type' => 'group',
                        'discount' => $discount,
                        'price' => $item->new_total_price,
                        'view' => $item->view,
                        'id' => $item->id,
                        'image' => $item->image,
                        'title' => $item->subject->title ?? '',
                        'language' => $item->language->title ?? '',
                        'flag' => $item->language->icon ?? '',
                        'professor' => $item->professor->full_name() ?? '',
                        'day' => $item->days . ' ' . (
                            $item->schedules->isNotEmpty()
                                ? \Carbon\Carbon::parse($item->schedules->first()->end_time)->format('H:i') . ' ' .
                                \Carbon\Carbon::parse($item->schedules->first()->start_time)->format('H:i')
                                : ''
                            ),
                        'start_date' => $item->start_date,
                        'end_date' => $item->end_date,                    ];

                case Webinar::class:
                    $discount = optional(
                        $item->discount
                    )->discount_percentage;
                    return [
                        'type' => 'webinar',
                        'discount' => $discount,
                        'id' => $item->id,
                        'image' => $item->image,
                        'date' => $item->date,
                        'price' => $item->new_total_price,
                        'time' => $item->hour,
                        'title' => $item->subject->title ?? '',
                        'language' => $item->language->icon ?? '',
                        'professor' => $item->professor->full_name() ?? '',
                        'rate' => $item->ratings()->avg('rating') ?? 0,
                        'rate_count' => $item->ratings()->count()?? 0,


                    ];

                case WorkShop::class:
                    $discount = optional(
                        $item->discount
                    )->discount_percentage;
                    return [
                        'type' => 'workshop',
                        'discount' => $discount,
                        'id' => $item->id,
                        'image' => $item->image,
                        'date' => $item->date,
                        'region' => $item->region,
                        'city' => $item->city->name,
                        'country' => $item->country->title,
                        'price' => $item->new_total_price,
                        'time' => $item->hour,
                        'max_capacity' => $item->max_capacity,
                        'title' => $item->subject->title ?? '',
                        'language' => $item->language->icon ?? '',
                        'professor' => $item->professor->full_name() ?? '',
                        'rate' => $item->ratings()->avg('rating') ?? 0,
                        'rate_count' => $item->ratings()->count()?? 0,
                    ];

                case Product::class:
                    $prof = BookLearning::where('product_id' , $item->id)->where('status' , 'approved')->get();

                    return [
                        'type' => 'book',
                        'id' => $item->id,
                        'image' => $item->images->first()->path ?? '',
                        'file' => $item->file,
                        'title' => $item->title,
                        'category' =>$item->category->title,
                        'language' => $item->language->title ?? '',
                        'flag' => $item->language->icon ?? '',
                        'subject' => $item->goal->title ?? '',
                        'Volume_number' => $item->product_type->title ?? '',
                        'price' => $item->price,
                        'discount' => $item->discount_percentage,
                        'professor' => $prof->map(function($prof){
                            return api::api.$prof->professors->profile;
                        }),
                    ];

                case Blog::class:
                    return [
                        'type' => 'blog',
                        'id' => $item->id,
                        'title' => $item->title,
                        'image' => $item->image,
                        'created_at' => $item->created_at,
                    ];

                case Exam::class:
                    return [
                        'type' => 'exam',
                        'id' => $item->id,
                        'title' => $item->name,
                        'image' => $item->image ?? 'http://api.lingomasters.ir/public/profiles/professor/1747998244.jpg',
                    ];

                default:
                    return null;
            }
        })        ->filter() // حذف nullها
                ->values() // بازسازی ایندکس‌ها
        );// حذف nullها و مرتب‌سازی

        return api_response($likes);
    }

    public function unlike(Request $request)
    {
        $request->validate([
            'likeable_type' => 'required|string',
            'likeable_id' => 'required|integer',
        ]);

        $user = auth()->user();

        $deleted = Like::where('user_id', $user->id)
            ->where('likeable_type', $request->likeable_type)
            ->where('likeable_id', $request->likeable_id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'لایک حذف شد.']);
        } else {
            return response()->json(['message' => 'لایک پیدا نشد.'], 404);
        }
    }

    public function bookLikes(Request $request)
    {
        $user = auth()->user();
        $categoryId = $request->query('category_id');

        $query = Like::with('likeable')
            ->where('user_id', $user->id)
            ->where('likeable_type', Product::class);

        if ($categoryId) {
            $query->whereHasMorph('likeable', [Product::class], function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $likes = $query->paginate();

        $likes->getCollection()->transform(function ($like) {
            $item = $like->likeable;
            if (!$item) return null;

            $prof = BookLearning::where('product_id', $item->id)
                ->where('status', 'approved')
                ->get();

            return [
                'title' => $item->title,
                'price' => $item->default_price,
                'off_price' => $item->default_discounted_price,
                'discount_percentage' => $item->default_discount_percentage,
                'discount_expiration' => $item->default_discount_expire_at,
                'language' => $item->language,
                'category' => $item->category->title ?? null,
                'image' => $item->images->first() ? api::api . '/' . $item->images->first()->path : null,
                'selected_seller' => $item->selected_seller,
                'is_like' => $item->is_liked,
                'is_download' => $item->is_download,
                'id' => $item->id,
                'professor' => $prof->map(function ($prof) {
                    return api::api . $prof->professors->profile;
                }),
            ];
        })->filter()->values();

        return api_response($likes);
    }


}

