<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Professors\GroupClass;
use App\Models\Professors\Webinar;
use App\Models\Professors\WorkShop;
use App\Models\shop\Comment;
use Illuminate\Http\Request;

class ProfessorsComments extends Controller
{
    public function TextComments1()
    {
        $user_id = 34;
//        $professor = auth()->user()->professor;
        $professor_id = 1;

        $groupClasses = GroupClass::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $webinars = Webinar::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $workshops = WorkShop::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $allItems = collect();

        foreach ($groupClasses as $class) {
            $comments = $class->comments->whereNotNull('body');
            $allItems->push([
                'id' => $class->id,
                'unique_id' => 1000000 + $class->id,
                'title' => $class->subject->title,
                'image' => $class->image ? api::api . $class->image : null,
                'type' => 'group',
                'type1' => 'کلاس گروهی',
                'has_more' => $comments->count() > 10,
                'created_at' => $class->created_at,
                'comments' => $comments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'content' => $comment->body,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                    ];
                }),
            ]);
        }

        foreach ($webinars as $webinar) {
            $comments = $webinar->comments->whereNotNull('body');
            $allItems->push([
                'id' => $webinar->id,
                'unique_id' => 2000000 + $webinar->id,
                'title' => $webinar->subject->title,
                'image' => $webinar->image ? api::api . $webinar->image : null,
                'type' => 'webinar',
                'type1' => 'وبینار',
                'has_more' => $comments->count() > 10,
                'created_at' => $webinar->created_at,
                'comments' => $comments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'content' => $comment->body,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                    ];
                }),
            ]);
        }

        foreach ($workshops as $workshop) {
            $comments = $workshop->comments->whereNotNull('body');
            $allItems->push([
                'id' => $workshop->id,
                'unique_id' => 3000000 + $workshop->id,
                'title' => $workshop->subject->title,
                'image' => $workshop->image ? api::api . $workshop->image : null,
                'type' => 'workshop',
                'type1' => 'ورکشاپ',
                'has_more' => $comments->count() > 10,
                'created_at' => $workshop->created_at,
                'comments' => $comments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'content' => $comment->body,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                    ];
                }),
            ]);
        }

        // اضافه کردن آیتم مربوط به پروفایل استاد
//        $professorComments = $professor->comments()->whereNotNull('body')->latest()->get();
//
//        $professorItem = [
//            'id' => $professor->id,
//            'unique_id' => 4000000 + $professor->id,
//            'title' => 'کامنت های شما',
//            'image' => $professor->user->profile ?? null,
//            'type' => 'professor',
//            'type1' => 'کلاس خصوصی',
//            'has_more' => $professorComments->count() > 10,
//            'created_at' => $professor->created_at,
//            'comments' => $professorComments->take(10)->map(function ($comment) use ($user_id) {
//                return [
//                    'id' => $comment->id,
//                    'name' => $comment->user->student->full_name(),
//                    'profile' => $comment->user->student->profile,
//                    'content' => $comment->body,
//                    'created_at' => $comment->created_at,
//                    'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
//                    'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
//                ];
//            }),
//        ];
//        $allItems->prepend($professorItem);


        // مرتب‌سازی سایر آیتم‌ها بر اساس created_at (به جز استاد که در ابتدای لیست باقی می‌ماند)
        $first = $allItems->first();
        $rest = $allItems->slice(1)->sortByDesc('created_at')->values();
        $sortedItems = collect([$first])->merge($rest);

        // پیجینیشن دستی
        $page = request()->get('page', 1);
        $perPage = 6;
        $total = $sortedItems->count();
        $paginatedItems = $sortedItems->forPage($page, $perPage)->values();
        $lastPage = ceil($total / $perPage);

        return response()->json([
            'massage' => 'ok',
            'response' => [
                'current_page' => (int)$page,
                'data' => $paginatedItems,
                'per_page' => $perPage,
                'total' => $total,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
                'last_page' => $lastPage,
                'first_page_url' => url()->current() . '?page=1',
                'last_page_url' => url()->current() . '?page=' . $lastPage,
                'next_page_url' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
                'prev_page_url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                'links' => [
                    [
                        'url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                        'label' => '&laquo; Previous',
                        'active' => $page > 1,
                    ],
                    [
                        'url' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
                        'label' => 'Next &raquo;',
                        'active' => $page < $lastPage,
                    ]
                ]
            ]
        ]);
    }

    public function TextComments()
    {
        $user_id = auth()->id();
        $professor = auth()->user()->professor;
        $professor_id = $professor->id;

        $groupClasses = GroupClass::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $webinars = Webinar::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $workshops = WorkShop::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $allItems = collect();

        foreach ($groupClasses as $class) {
            $comments = $class->comments->whereNotNull('body');
            $allItems->push([
                'id' => $class->id,
                'unique_id' => 1000000 + $class->id,
                'title' => $class->subject->title,
                'image' => $class->professor->profile ? api::api . $class->professor->profile : null,
                'type' => 'group',
                'type1' => 'کلاس گروهی',
                'has_more' => $comments->count() > 10,
                'created_at' => $class->created_at,
                'comments' => $comments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile ? api::api.$comment->user->student->profile:null ,
                        'content' => $comment->body,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                    ];
                }),
            ]);
        }

        foreach ($webinars as $webinar) {
            $comments = $webinar->comments->whereNotNull('body');
            $allItems->push([
                'id' => $webinar->id,
                'unique_id' => 2000000 + $webinar->id,
                'title' => $webinar->subject->title,
                'image' => $webinar->image ? api::api . $webinar->image : null,
                'type' => 'webinar',
                'type1' => 'وبینار',
                'has_more' => $comments->count() > 10,
                'created_at' => $webinar->created_at,
                'comments' => $comments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'content' => $comment->body,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                    ];
                }),
            ]);
        }

        foreach ($workshops as $workshop) {
            $comments = $workshop->comments->whereNotNull('body');
            $allItems->push([
                'id' => $workshop->id,
                'unique_id' => 3000000 + $workshop->id,
                'title' => $workshop->subject->title,
                'image' => $workshop->image ? api::api . $workshop->image : null,
                'type' => 'workshop',
                'type1' => 'ورکشاپ',
                'has_more' => $comments->count() > 10,
                'created_at' => $workshop->created_at,
                'comments' => $comments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'content' => $comment->body,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                    ];
                }),
            ]);
        }

        // اضافه کردن آیتم مربوط به پروفایل استاد
        $professorComments = $professor->comments()->whereNotNull('body')->latest()->get();

        $professorItem = [
            'id' => $professor->id,
            'unique_id' => 4000000 + $professor->id,
            'title' => 'کامنت های شما',
            'image' => $professor->user->profile ?? null,
            'type' => 'professor',
            'type1' => 'کلاس خصوصی',
            'has_more' => $professorComments->count() > 10,
            'created_at' => $professor->created_at,
            'comments' => $professorComments->take(10)->map(function ($comment) use ($user_id) {
                return [
                    'id' => $comment->id,
                    'name' => $comment->user->student->full_name(),
                    'profile' => $comment->user->student->profile,
                    'content' => $comment->body,
                    'created_at' => $comment->created_at,
                    'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                    'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                ];
            }),
        ];
        $allItems->prepend($professorItem);


        // مرتب‌سازی سایر آیتم‌ها بر اساس created_at (به جز استاد که در ابتدای لیست باقی می‌ماند)
        $first = $allItems->first();
        $rest = $allItems->slice(1)->sortByDesc('created_at')->values();
        $sortedItems = collect([$first])->merge($rest);

        // پیجینیشن دستی
        $page = request()->get('page', 1);
        $perPage = 6;
        $total = $sortedItems->count();
        $paginatedItems = $sortedItems->forPage($page, $perPage)->values();
        $lastPage = ceil($total / $perPage);

        return response()->json([
            'massage' => 'ok',
            'response' => [
                'current_page' => (int)$page,
                'data' => $paginatedItems,
                'per_page' => $perPage,
                'total' => $total,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
                'last_page' => $lastPage,
                'first_page_url' => url()->current() . '?page=1',
                'last_page_url' => url()->current() . '?page=' . $lastPage,
                'next_page_url' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
                'prev_page_url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                'links' => [
                    [
                        'url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                        'label' => '&laquo; Previous',
                        'active' => $page > 1,
                    ],
                    [
                        'url' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
                        'label' => 'Next &raquo;',
                        'active' => $page < $lastPage,
                    ]
                ]
            ]
        ]);
    }

    public function VoiceComments()
    {
        $user_id = auth()->id();
        $professor_id = auth()->user()->professor->id;

        $professor = auth()->user()->professor;


        $groupClasses = GroupClass::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $webinars = Webinar::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $workshops = WorkShop::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $allItems = collect();

        foreach ($groupClasses as $class) {
            $voiceComments = $class->comments->whereNotNull('voice_url');
            $allItems->push([
                'id' => $class->id,
                'unique_id' => 1000000 + $class->id,
                'title' => $class->subject->title,
                'image' => $class->image ? api::api . $class->image : null,
                'type' => 'group',
                'type1' => 'کلاس گروهی',
                'has_more' => $voiceComments->count() > 10,
                'created_at' => $class->created_at,
                'comments' => $voiceComments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'voice_url' => $comment->voice_url ?api::api.$comment->voice_url:null ,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                        'likes_count' => $comment->likes->count(),
                        'dislikes_count' => $comment->dislikes->count(),
                    ];
                }),
            ]);
        }

        foreach ($webinars as $webinar) {
            $voiceComments = $webinar->comments->whereNotNull('voice_url');
            $allItems->push([
                'id' => $webinar->id,
                'unique_id' => 4000000 + $webinar->id,
                'title' => $webinar->subject->title,
                'image' => $webinar->image ? api::api . $webinar->image : null,
                'type' => 'webinar',
                'type1' => 'وبینار',
                'has_more' => $voiceComments->count() > 10,
                'created_at' => $webinar->created_at,
                'comments' => $voiceComments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'voice_url' => $comment->voice_url ?api::api.$comment->voice_url:null,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                        'likes_count' => $comment->likes->count(),
                        'dislikes_count' => $comment->dislikes->count(),
                    ];
                }),
            ]);
        }

        foreach ($workshops as $workshop) {
            $voiceComments = $workshop->comments->whereNotNull('voice_url');
            $allItems->push([
                'id' => $workshop->id,
                'unique_id' => 3000000 + $workshop->id,
                'title' => $workshop->subject->title,
                'image' => $workshop->image ? api::api . $workshop->image : null,
                'type' => 'workshop',
                'type1' => 'ورکشاپ',
                'has_more' => $voiceComments->count() > 10,
                'created_at' => $workshop->created_at,
                'comments' => $voiceComments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'voice_url' => $comment->voice_url ?api::api.$comment->voice_url:null,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                        'likes_count' => $comment->likes->count(),
                        'dislikes_count' => $comment->dislikes->count(),
                    ];
                }),
            ]);
        }
        $professor_id = $professor->id;

        $professorComments = $professor->comments()->whereNotNull('voice_url')->latest()->get();

        $professorItem = [
            'id' => $professor->id,
            'unique_id' => 4000000 + $professor->id,
            'title' => 'کامنت های شما',
            'image' => $professor->user->profile ?? null,
            'type' => 'professor',
            'type1' => 'کلاس خصوصی',
            'has_more' => $professorComments->count() > 10,
            'created_at' => $professor->created_at,
            'comments' => $professorComments->take(10)->map(function ($comment) use ($user_id) {
                return [
                    'id' => $comment->id,
                    'name' => $comment->user->student->full_name(),
                    'profile' => $comment->user->student->profile,
                    'voice_url' => $comment->voice_url ? api::api.$comment->voice_url : null,
                    'created_at' => $comment->created_at,
                    'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                    'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                ];
            }),
        ];
        $allItems->prepend($professorItem);


        // مرتب‌سازی سایر آیتم‌ها بر اساس created_at (به جز استاد که در ابتدای لیست باقی می‌ماند)
        $first = $allItems->first();
        $rest = $allItems->slice(1)->sortByDesc('created_at')->values();
        $sortedItems = collect([$first])->merge($rest);

        $page = request()->get('page', 1);
        $perPage = 6;
        $total = $sortedItems->count();
        $paginatedItems = $sortedItems->forPage($page, $perPage)->values();
        $lastPage = ceil($total / $perPage);

        return response()->json([
            'massage' => 'ok',
            'response' => [
                'current_page' => (int)$page,
                'data' => $paginatedItems,
                'per_page' => $perPage,
                'total' => $total,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
                'last_page' => $lastPage,
                'first_page_url' => url()->current() . '?page=1',
                'last_page_url' => url()->current() . '?page=' . $lastPage,
                'next_page_url' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
                'prev_page_url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                'links' => [
                    [
                        'url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                        'label' => '&laquo; Previous',
                        'active' => $page > 1,
                    ],
                    [
                        'url' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
                        'label' => 'Next &raquo;',
                        'active' => $page < $lastPage,
                    ]
                ]
            ]
        ]);
    }

    public function VideoComments()
    {
        $user_id = auth()->id();
        $professor_id = auth()->user()->professor->id;
        $professor = auth()->user()->professor;
        $professor_id = $professor->id;
        $groupClasses = GroupClass::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $webinars = Webinar::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $workshops = WorkShop::where('professors_id', $professor_id)
            ->with(['comments' => function ($query) {
                $query->latest();
            }, 'subject'])
            ->latest()
            ->get();

        $allItems = collect();

        foreach ($groupClasses as $class) {
            $videoComments = $class->comments->whereNotNull('video_url');
            $allItems->push([
                'id' => $class->id,
                'unique_id' => 1000000 + $class->id,
                'title' => $class->subject->title,
                'image' => $class->image ? api::api . $class->image : null,
                'type' => 'group',
                'type1' => 'کلاس گروهی',
                'has_more' => $videoComments->count() > 10,
                'created_at' => $class->created_at,
                'comments' => $videoComments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'video_url' => $comment->video_url ? api::api.$comment->video_url :null,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                        'likes_count' => $comment->likes->count(),
                        'dislikes_count' => $comment->dislikes->count(),
                    ];
                }),
            ]);
        }

        foreach ($webinars as $webinar) {
            $videoComments = $webinar->comments->whereNotNull('video_url');
            $allItems->push([
                'id' => $webinar->id,
                'unique_id' => 4000000 + $webinar->id,
                'title' => $webinar->subject->title,
                'image' => $webinar->image ? api::api . $webinar->image : null,
                'type' => 'webinar',
                'type1' => 'وبینار',
                'has_more' => $videoComments->count() > 10,
                'created_at' => $webinar->created_at,
                'comments' => $videoComments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'video_url' => $comment->video_url ? api::api.$comment->video_url :null,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                        'likes_count' => $comment->likes->count(),
                        'dislikes_count' => $comment->dislikes->count(),
                    ];
                }),
            ]);
        }

        foreach ($workshops as $workshop) {
            $videoComments = $workshop->comments->whereNotNull('video_url');
            $allItems->push([
                'id' => $workshop->id,
                'unique_id' => 3000000 + $workshop->id,
                'title' => $workshop->subject->title,
                'image' => $workshop->image ? api::api . $workshop->image : null,
                'type' => 'workshop',
                'type1' => 'ورکشاپ',
                'has_more' => $videoComments->count() > 10,
                'created_at' => $workshop->created_at,
                'comments' => $videoComments->take(10)->map(function ($comment) use ($user_id) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->student->full_name(),
                        'profile' => $comment->user->student->profile,
                        'video_url' =>$comment->video_url ? api::api.$comment->video_url :null,
                        'created_at' => $comment->created_at,
                        'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                        'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                        'likes_count' => $comment->likes->count(),
                        'dislikes_count' => $comment->dislikes->count(),
                    ];
                }),
            ]);
        }


        $professorComments = $professor->comments()->whereNotNull('video_url')->latest()->get();

        $professorItem = [
            'id' => $professor->id,
            'unique_id' => 4000000 + $professor->id,
            'title' => 'کامنت های شما',
            'image' => $professor->user->profile ?? null,
            'type' => 'professor',
            'type1' => 'کلاس خصوصی',
            'has_more' => $professorComments->count() > 10,
            'created_at' => $professor->created_at,
            'comments' => $professorComments->take(10)->map(function ($comment) use ($user_id) {
                return [
                    'id' => $comment->id,
                    'name' => $comment->user->student->full_name(),
                    'profile' => $comment->user->student->profile,
                    'video_url' => $comment->video_url ? api::api.$comment->video_url : null,
                    'created_at' => $comment->created_at,
                    'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                    'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                ];
            }),
        ];
        $allItems->prepend($professorItem);


        // مرتب‌سازی سایر آیتم‌ها بر اساس created_at (به جز استاد که در ابتدای لیست باقی می‌ماند)
        $first = $allItems->first();
        $rest = $allItems->slice(1)->sortByDesc('created_at')->values();
        $sortedItems = collect([$first])->merge($rest);

        $page = request()->get('page', 1);
        $perPage = 6;
        $total = $sortedItems->count();
        $paginatedItems = $sortedItems->forPage($page, $perPage)->values();
        $lastPage = ceil($total / $perPage);

        return response()->json([
            'massage' => 'ok',
            'response' => [
                'current_page' => (int)$page,
                'data' => $paginatedItems,
                'per_page' => $perPage,
                'total' => $total,
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
                'last_page' => $lastPage,
                'first_page_url' => url()->current() . '?page=1',
                'last_page_url' => url()->current() . '?page=' . $lastPage,
                'next_page_url' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
                'prev_page_url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                'links' => [
                    [
                        'url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                        'label' => '&laquo; Previous',
                        'active' => $page > 1,
                    ],
                    [
                        'url' => $page < $lastPage ? url()->current() . '?page=' . ($page + 1) : null,
                        'label' => 'Next &raquo;',
                        'active' => $page < $lastPage,
                    ]
                ]
            ]
        ]);
    }


    public function showGroupClassComments(Request $request, $id)
    {
        $user_id = auth()->id();
        $type = $request->input('type');
        $perPage = $request->input('per_page', 10);

        $groupClass = GroupClass::with('subject')->findOrFail($id);

        $commentsQuery = Comment::where('commentable_type', GroupClass::class)
            ->where('commentable_id', $id)
            ->with('user.student')
            ->latest();

        if ($type === 'text') {
            $commentsQuery->whereNotNull('body');
        } elseif ($type === 'voice') {
            $commentsQuery->whereNotNull('voice_url');
        } elseif ($type === 'video') {
            $commentsQuery->whereNotNull('video_url');
        } else {
            return api_response([], 'کامنتی یافت نشد');
        }

        $paginated = $commentsQuery->paginate($perPage);

        $comments = $paginated->map(function ($comment) use ($user_id) {
            return [
                'id' => $comment->id,
                'name' => optional($comment->user->student)->full_name(),
                'profile' => optional($comment->user->student)->profile,
                'content' => $comment->body,
                'voice_url' => $comment->voice_url ?api::api.$comment->voice_url:null,
                'video_url' => $comment->video_url ? api::api.$comment->video_url :null,
                'created_at' => $comment->created_at,
                'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                'likes_count' => $comment->likes->count(),
                'dislikes_count' => $comment->dislikes->count(),
            ];
        });

        return api_response([
            'class_id' => $groupClass->id,
            'class_title' => $groupClass->subject->title,
            'type' => 'group',
            'type1' => 'کلاس گروهی',
            'comments' => $comments,
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }


    public function showWebinarComments(Request $request, $id)
    {
        $user_id = auth()->id();
        $type = $request->input('type');
        $perPage = $request->input('per_page', 10);

        $webinar = Webinar::with('subject')->findOrFail($id);

        $commentsQuery = Comment::where('commentable_type', Webinar::class)
            ->where('commentable_id', $id)
            ->with('user.student')
            ->latest();

        if ($type === 'text') {
            $commentsQuery->whereNotNull('body');
        } elseif ($type === 'voice') {
            $commentsQuery->whereNotNull('voice_url');
        } elseif ($type === 'video') {
            $commentsQuery->whereNotNull('video_url');
        } else {
            return api_response([], 'کامنتی یافت نشد');
        }

        $paginated = $commentsQuery->paginate($perPage);

        $comments = $paginated->map(function ($comment) use ($user_id) {
            return [
                'id' => $comment->id,
                'name' => optional($comment->user->student)->full_name(),
                'profile' => optional($comment->user->student)->profile,
                'content' => $comment->body,
                'voice_url' =>  $comment->voice_url ?api::api.$comment->voice_url:null,
                'video_url' => $comment->video_url ? api::api.$comment->video_url :null,
                'created_at' => $comment->created_at,
                'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                'likes_count' => $comment->likes->count(),
                'dislikes_count' => $comment->dislikes->count(),
            ];
        });

        return api_response([
            'class_id' => $webinar->id,
            'class_title' => $webinar->subject->title,
            'type' => 'webinar',
            'type1' => 'وبینار',
            'comments' => $comments,
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),

        ]);
    }
    public function showWorkShopComments(Request $request, $id)
    {
        $user_id = auth()->id();
        $type = $request->input('type');
        $perPage = $request->input('per_page', 10);

        $workshop = WorkShop::with('subject')->findOrFail($id);

        $commentsQuery = Comment::where('commentable_type', WorkShop::class)
            ->where('commentable_id', $id)
            ->with('user.student')
            ->latest();

        if ($type === 'text') {
            $commentsQuery->whereNotNull('body');
        } elseif ($type === 'voice') {
            $commentsQuery->whereNotNull('voice_url');
        } elseif ($type === 'video') {
            $commentsQuery->whereNotNull('video_url');
        } else {
            return api_response([], 'کامنتی یافت نشد');
        }

        $paginated = $commentsQuery->paginate($perPage);

        $comments = $paginated->map(function ($comment) use ($user_id) {
            return [
                'id' => $comment->id,
                'name' => optional($comment->user->student)->full_name(),
                'profile' => optional($comment->user->student)->profile,
                'content' => $comment->body,
                'voice_url' =>  $comment->voice_url ?api::api.$comment->voice_url:null,
                'video_url' => $comment->video_url ? api::api.$comment->video_url :null,
                'created_at' => $comment->created_at,
                'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
                'likes_count' => $comment->likes->count(),
                'dislikes_count' => $comment->dislikes->count(),
            ];
        });

        return api_response([
            'class_id' => $workshop->id,
            'class_title' => $workshop->subject->title,
            'type' => 'WorkShop',
            'type1' => 'ورک شاپ',
            'comments' => $comments,
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),

        ]);
    }

}
