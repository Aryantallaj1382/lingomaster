<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Professors\GroupClass;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\Professor;
use App\Models\Professors\Webinar;
use App\Models\Professors\WorkShop;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;


class SearchController extends Controller
{
    public function q_search(Request $request)
    {
        $query = $request->query('search');

        $webinars = Webinar::whereRelation('subject', 'title', 'LIKE', "%$query%")
            ->with('subject:id,title')          // eager load سبک
            ->get()
            ->map(fn($w) => [
                'title' => 'وبینار ' . $w->subject->title,
                'id' => $w->id,
                'type' => 'webinar',
            ])
            ->toBase();                         // ⬅️ تبدیل

        $workshops = Workshop::whereRelation('subject', 'title', 'LIKE', "%$query%")
            ->with('subject:id,title')
            ->get()
            ->map(fn($w) => [
                'title' => 'ورکشاپ ' . $w->subject->title,
                'id' => $w->id,
                'type' => 'workshop',
            ])
            ->toBase();

        $professors = Professor::where('first_name', 'like', "%$query%")
            ->orWhere('last_name', 'like', "%$query%")
            ->get()
            ->map(fn($p) => [
                'title' => 'استاد ' . $p->full_name(),
                'id' => $p->id,
                'type' => 'private-class',
            ])
            ->toBase();

        $groupClasses = GroupClass::whereRelation('subject', 'title', 'LIKE', "%$query%")
            ->with('subject:id,title')
            ->get()
            ->map(fn($g) => [
                'title' => 'کلاس گروهی ' . $g->subject->title,
                'id' => $g->id,
                'type' => 'group-class',
            ])
            ->toBase();

        $results = $webinars
            ->merge($workshops)
            ->merge($professors)
            ->merge($groupClasses)
            ->values();   // ایندکس‌های 0..n

        return api_response($results);
    }

    public function filters()
    {
        return api_response([
            'types' => [
                'webinar' => 'وبینار',
                'workshop' => 'ورکشاپ',
                'group_class' => 'کلاس گروهی',
                'private_class' => 'کلاس خصوصی',
            ],
            'languages' => Languages::select('id', 'title', 'flag')->get()->map(fn($l) => [
                'value' => $l->id,
                'name' => $l->title,
                'flag' => $l->flag,
            ]),
            'levels' => LanguageLevel::select('id', 'title')->get()->map(fn($l) => [
                'value' => $l->id,
                'name' => $l->title,
            ]),
            'age_groups' => AgeGroup::select('id', 'title')->get()->map(fn($l) => [
                'value' => $l->id,
                'name' => $l->title,
            ]),
        ]);
    }

    public function search(Request $request)
    {
        // ---------- پارامترها ----------
        $text = $request->query('search', '');
        $types = (array)$request->query('type', []);        // فیلتر نوع کلاس
        $languageId = $request->query('language_id');
        $levelId = $request->query('level_id');
        $ageGroupId = $request->query('age_group_id');
        $perPage = (int)$request->query('per_page', 15);
        $page = (int)$request->query('page', 1);

        // ---------- تعریف منابع ----------
        $sources = [
            'webinar' => [
                'builder' => Webinar::query()
                    ->whereRelation('subject', 'title', 'LIKE', "%$text%")
                    ->with('subject:id,title', 'language:id,title,flag', 'professor:id,first_name,last_name'),
                'map' => fn($w) => [
                    'title' => 'وبینار ' . $w->subject->title,
                    'id' => $w->id,
                    'image' => $w->image,
                    'price' => $w->total_price,
                    'total_price' => $w->new_total_price,
                    'discount' => $w->total_percentage ?? null,
                    'price_discount' => $w->new_total_price,
                    'isLiked' => auth()->id() ? $w->isLikedByUser(auth()->id()) : null,
                    'language' => $w->language->title,
                    'flag' => api::api . $w->language->flag,
                    'professor' => $w->professor->full_name(),
                    'type' => 'webinar',
                ],
            ],

            'workshop' => [
                'builder' => Workshop::query()
                    ->whereRelation('subject', 'title', 'LIKE', "%$text%")
                    ->with('subject:id,title', 'language:id,title,flag', 'professor:id,first_name,last_name'),
                'map' => fn($w) => [
                    'title' => 'ورکشاپ ' . $w->subject->title,
                    'id' => $w->id,
                    'image' => $w->image,
                    'isLiked' => auth()->id() ? $w->isLikedByUser(auth()->id()) : null,
                    'language' => $w->language->title,
                    'flag' => $w->language->icon,
                    'professor' => $w->professor->full_name(),
                    'type' => 'workshop',
                    'price' => $w->total_price,
                    'total_price' => $w->new_total_price,
                    'discount' => $w->total_percentage ?? null,
                    'price_discount' => $w->new_total_price,
                ],
            ],

            'private-class' => [
                'builder' => Professor::query()
                    ->where(fn($q) => $q->where('first_name', 'like', "%$text%")
                        ->orWhere('last_name', 'like', "%$text%"))
                    ->with('languages:id,title,flag'),
                'map' => fn($p) => [
                    'professor' => 'استاد ' . $p->full_name(),
                    'id' => $p->id,
                    'type' => 'private-class',
                    'view' => $p->views_count,
                    'isLiked' => auth()->id() ? $p->isLikedByUser(auth()->id()) : null,
                    'image' => $p->profile ? api::api . $p->profile : null,
                    'language' => optional($p->languages->first())->title,
                    'flag' => $p->languages?->first()?->icon,
                    'price_discount' => $p->ClassPrice->where('class_type', 'private')?->first()?->base_price,
                    'price' => $p->ClassPrice->where('class_type', 'private')?->first()?->base_price,

                ],
            ],

            'group-class' => [
                'builder' => GroupClass::query()
                    ->whereRelation('subject', 'title', 'LIKE', "%$text%")
                    ->with('subject:id,title', 'language:id,title,flag', 'professor:id,first_name,last_name', 'discount'),
                'map' => fn($g) => [
                    'title' => 'کلاس گروهی ' . $g->subject->title,
                    'id' => $g->id,
                    'view' => $g->view,
                    'price' => $g->new_total_price,
                    'discount' => $g->discount->discount_percentage ?? null,
                    'price_discount' => $g->new_total_price - ($g->new_total_price * ($g->discount->discount_percentage ?? 0) / 100),
                    'image' => $g->image,
                    'isLiked' => auth()->id() ? $g->isLikedByUser(auth()->id()) : null,
                    'language' => $g->language->title,
                    'flag' => $g->language->icon,
                    'professor' => $g->professor->full_name(),
                    'type' => 'group-class',
                ],
            ],
        ];

        // ---------- اجرای فیلتر نوع ----------
        if (!empty($types)) {
            $sources = array_intersect_key($sources, array_flip($types));
        }

        // ---------- جمع‌آوری نتایج ----------
        $results = collect();

        foreach ($sources as $def) {
            $results = $results->merge(
                $this->applyCommonFilters($def['builder'], $languageId, $levelId, $ageGroupId)
                    ->get()
                    ->map($def['map'])
                    ->toBase()
            );
        }

        // ---------- صفحه‌بندی ----------
        $total = $results->count();
        $items = $results->values()->forPage($page, $perPage)->values();

        $paginated = new LengthAwarePaginator(
            $items, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return api_response($paginated);
    }


    private function applyCommonFilters($builder, $languageId, $levelId, $ageGroupId)
    {
        return $builder
            ->when($languageId, fn($q) => $q->where('language_id', $languageId))
            ->when($levelId, fn($q) => $q->where('level_id', $levelId))
            ->when($ageGroupId, fn($q) => $q->where('age_group_id', $ageGroupId));
    }

}
