<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\sendMessageController;
use App\Models\Blog;
use App\Models\Consultation;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Like;
use App\Models\Professors\GroupClass;
use App\Models\Professors\Professor;
use App\Models\shop\Slaider;
use App\Models\shop\Story;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    public function index(Request $request)
    {

        $language = Languages::select(['id', 'title', 'flag'])->get();
        $la = $language->map(function ($lang) {
            return [
              'id'=> $lang->id,
              'title' => $lang->title,
              'flag' => $lang->flag ? api::api.$lang->flag : null,
            ];
        });
        $story = Story::where('who', 'professor')->where('is_approved', 1)->latest()->get()->map(function ($story) {
            return [
                    'id' => $story->id ,
                    'title' =>  $story->user()->first()?->professor?->full_name() ?? 'نامشخص',
                    'video' => $story->video,
                    'cover' => $story->cover  ? api::api.$story->user()->first()?->professor?->profile: null,
                    'professor' => $story->user()->first()?->professor?->full_name() ?? 'نامشخص',
            ];
        });
        $group = GroupClass::with(['subject', 'language', 'professor', 'schedules', 'discount'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($group) {
                $firstSchedule = $group->schedules->first();
                if (auth()->user()) {
                    $is_like = Like::where('user_id', auth()->user()->id)->where('likeable_id', $group->id)->where('likeable_type', GroupClass::class)->exists();
                }
                return [
                    'id'=>$group->id,
                    'name' => $group->subject->title,
                    'language' => $group->language->title,
                    'professor' => $group->professor->full_name() ?? null   ,
                    'start_date' => $group->start_date,
                    'end_date' => $group->end_date,
                    'day' => $group->days . ' ' .
                        optional($firstSchedule)->start_time . '-' .
                        optional($firstSchedule)->end_time,
                    'price' => $group->new_total_price,
                    'discount' => optional($group->discount)->discount_percentage ?? 0,
                    'is_like' => $is_like ?? false,
                    'view' => $group->view,

                ];
            });

        $blog = Blog::latest()->take(3)->get()->map(function ($blog) {
            return [
                'id'=>$blog->id,
                'image' => $blog->image ?? null,
                'title' => $blog->title ?? null,
                'created_at' => $blog->created_at ?? null,
            ];
        });

        $discount = $this->discountedItems();

        $sliders = Slaider::where('location','mainPage')->get()->map(function ($slider) {
            return [
                'id' => $slider->id,
                'link' => $slider->link,
                'tablet_image' => $slider->tablet_image,
                'laptop_image' => $slider->laptop_image,
                'mobile_image' => $slider->mobile_image,
            ];
        });


        return api_response([
            'language' => $la,
            'story' => $story,
            'group' => $group,
            'blog' => $blog,
            'discount' => $discount,
            'slider' =>$sliders,
        ]);
    }
    public function discountedItems()
    {
        // اساتید دارای تخفیف روی کلاس خصوصی
        $discountedProfessors = Professor::visible()->whereHas('ClassPrice.discount', function ($q) {
            $q->where('discount_type', 'general')
                ->where('class_mode', 'private')
                ->where('discount_percentage', '>', 0);
        })
            ->with([
                'languages',
                'addresses.city',
                'addresses.country',
                'teachingTypes',
                'ratings',
                'ClassPrice' => function ($q) {
                    $q->where('class_type', 'private')->with('discount');
                }
            ])
            ->take(6)
            ->get()
            ->map(function ($professor) {
                $language = $professor->languages->first();
                $classPrice = $professor->ClassPrice->first();
                $discount = optional(optional($classPrice)->discount)->discount_percentage ?? 0;

                if (auth()->check()) {
                    $is_like = Like::where('user_id', auth()->id())
                        ->where('likeable_id', $professor->id)
                        ->where('likeable_type', Professor::class)
                        ->exists();
                }

                return [
                    'id' => $professor->id,
                    'type' => 'professor',
                    'professor' => $professor->full_name(),
                    'profile' => $professor->profile ? api::api . $professor->profile : null,
                    'language' => $language?->title,
                    'flag' => $language?->icon,
                    'views_count' => $professor->views_count,
                    'city' => $professor->addresses->first()?->city?->name,
                    'country' => $professor->addresses->first()?->country?->title,
                    'teachingTypes' => $professor->teachingTypes->first()?->title,
                    'rate' => min(5, max(0, round($professor->ratings->avg('rating') ?? 0))),
                    'price' => optional($classPrice)->base_price ?? 0,
                    'discount' => $discount,
                    'is_like' => $is_like ?? false,
                ];
            });

        $discountedGroups = GroupClass::whereHas('discount', function ($q) {
            $q->where('discount_percentage', '>', 0);
        })
            ->with(['subject', 'language', 'professor', 'schedules', 'discount'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function ($group) {
                $firstSchedule = $group->schedules->first();
                if (auth()->check()) {
                    $is_like = Like::where('user_id', auth()->id())
                        ->where('likeable_id', $group->id)
                        ->where('likeable_type', GroupClass::class)
                        ->exists();
                }

                return [
                    'id'=>$group->id,
                    'type' => 'group_class',
                    'name' => $group->subject->title,
                    'language' => $group->language->title,
                    'professor' => $group->professor->full_name(),
                    'start_date' => $group->start_date,
                    'end_date' => $group->end_date,
                    'day' => $group->days . ' ' .
                        optional($firstSchedule)->start_time . '-' .
                        optional($firstSchedule)->end_time,
                    'price' => $group->new_total_price,
                    'discount' => optional($group->discount)->discount_percentage ?? 0,
                    'is_like' => $is_like ?? false,
                    'view' => $group->view,
                ];
            });

        // ترکیب هر دو لیست
        $discountedProfessors = $discountedProfessors->map(function ($item) {
            return is_array($item) ? $item : $item->toArray();
        });

        $discountedGroups = $discountedGroups->map(function ($item) {
            return is_array($item) ? $item : $item->toArray();
        });

        return $discountedGroups->merge($discountedProfessors)->values();
    }


    public function class(Request $request)
    {
        $lang = $request->input('lang');

        $professors = Professor::visible()->whereHas('languages', function ($q) use ($lang) {
            $q->where('languages.id', $lang);
        })
            ->with([
                'languages' => function ($q) use ($lang) {
                    $q->where('languages.id', $lang);
                },
                'addresses.city',
                'addresses.country',
                'teachingTypes',
                'ratings',

                'ClassPrice' => function ($q) {
                    $q->where('class_type', 'private');
                }
            ])
            ->take(6)
            ->get()
            ->map(function ($professor) use ($lang) {
                $language = $professor->languages->first();

                if (auth()->user()) {
                    $is_like = Like::where('user_id', auth()->user()->id)
                        ->where('likeable_id', $professor->id)
                        ->where('likeable_type', Professor::class)
                        ->exists();
                }

                // بررسی کلاس آنلاین و حضوری
                $hasOnlineClass = $professor->online()
                    ->where('language_id', $lang)
                    ->exists();

                $hasOfflineClass = $professor->offline()
                    ->where('language_id', $lang)
                    ->exists();

                $teachingTypes = [];
                if ($hasOnlineClass) {
                    $teachingTypes[] = 'آنلاین';
                }
                if ($hasOfflineClass) {
                    $teachingTypes[] = 'حضوری';
                }

                return [
                    'id' => $professor->id,
                    'professor' => $professor->full_name(),
                    'profile' => $professor->profile ? api::api . $professor->profile : null,
                    'language' => $language?->title,
                    'flag' => $language?->icon,
                    'views_count' => $professor->views_count,
                    'city' => $professor->addresses->first()?->city?->name,
                    'country' => $professor->addresses->first()?->country?->title,
                    'teachingTypes' => implode(' / ', $teachingTypes), // تبدیل آرایه به رشته خوانا
                    'rate' => min(5, max(0, round($professor->ratings->avg('rating') ?? 0))),
                    'price' => $professor->ClassPrice->first()?->base_price ?? 0,
                    'discount' => optional(
                            $professor->classPrice()
                                ->where('class_type', 'private')
                                ->where('professor_id', $professor->id)
                                ->first()?->discount
                        )->discount_percentage ?? 0,
                    'is_like' => $is_like ?? false,
                    'resume' => null
                ];
            });


        return api_response($professors);
    }

    public function consultation_form(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'mobile' => 'required',
            'time' => 'required',
            'description' => 'required',

        ]);

        Consultation::created($request->all());
        return api_response([],"در خواست شما ثبت شد و در اسراع وقت با شما تماس گرفته خواهد شد");

    }
}
