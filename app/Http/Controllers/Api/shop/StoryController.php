<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\contacts\role_value;
use App\Models\Professors\Professor;
use App\Models\shop\Comment;
use App\Models\shop\Seller;
use App\Models\shop\Story;
use App\Models\User;
use Illuminate\Http\Request;

class StoryController extends Controller
{

    public function getUserRole($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'کاربر یافت نشد.',
            ], 404);
        }
        return response()->json([
            'status' => 'success',
            'role' => $user->roles->first()->name,
        ]);
    }

    public function index()
    {
        $stories = Story::where('is_approved', true)->all();
        return api_response($stories);
    }

    public function show($id)
    {
        $story = Story::with(['likes'])
            ->where('id', $id)
            ->where('is_approved', true)
            ->firstOrFail();

        $user = $story->user;
        $user1 = auth()->user();

        $page = request('page', 1);

        $commentsQuery = Comment::with(['user', 'likes'])
            ->where('commentable_type', Story::class)
            ->where('commentable_id', $story->id);

        $comments = $commentsQuery->latest()->paginate(10, ['*'], 'page', $page);
        if ($user->roles->contains('name', 'seller')) {
            $formattedStory = [
                'id' => $story->id,
                'cover' => $story->cover ? asset($story->cover) : api::api . $story->user->profile_image,
                'video' => $story->video ? asset($story->video) : null,
                'title' => $story->title,
                'is_main' => $user1 && $story->user_id === $user1->id,

                'seller' => [
                    'id' => $story->user->seller->id,
                    'name' => $story->user->seller->name,
                    'image' => $story->user->seller->profile ? api::api . $story->user->seller->profile : null,
                ],
                'likes_count' => $story->likes()->count(),
                'is_liked' => $user1 ? $story->likes()->where('user_id', $user1->id)->exists() : false,
                'comments_count' => $comments->total(), // تعداد کل کامنت‌ها
                'comments' => $comments->map(function ($comment) use ($user1) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->getFullName(),
                        'body' => $comment->body,
                        'profile_image' => $comment->user->profile_image ? api::api . $comment->user->profile_image : null,
                        'likes_count' => $comment->likes()->count(),
                        'is_liked' => $user1 ? $comment->likes()->where('user_id', $user1->id)->exists() : false,
                        'is_mine' => $user1 ? $comment->user_id == $user1->id : false,
                        'created_at' => $comment->created_at
                    ];
                }),
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                ],
            ];
        }
        if ($user->roles->contains('name', 'professor')) {
            $formattedStory = [
                'id' => $story->id,
                'cover' => $story->cover ? asset($story->cover) : api::api . $story->user->profile_image,
                'video' => $story->video ? asset($story->video) : null,
                'title' => $story->title,
                'is_main' => $user1 && $story->user_id === $user1->id,

                'seller' => [
                    'id' => $story->user->professor->id,
                    'name' => $story->user->professor->full_name(),
                    'image' => $story->user->professor->profile ? api::api . $story->user->professor->profile : null,
                ],
                'likes_count' => $story->likes()->count(),
                'is_liked' => $user1 ? $story->likes()->where('user_id', $user1->id)->exists() : false,
                'comments_count' => $comments->total(), // تعداد کل کامنت‌ها
                'comments' => $comments->map(function ($comment) use ($user1) {
                    return [
                        'id' => $comment->id,
                        'name' => $comment->user->getFullName(),
                        'body' => $comment->body,
                        'profile_image' => $comment->user->profile_image ? api::api . $comment->user->profile_image : null,
                        'likes_count' => $comment->likes()->count(),
                        'is_liked' => $user1 ? $comment->likes()->where('user_id', $user1->id)->exists() : false,
                        'is_mine' => $user1 ? $comment->user_id == $user1->id : false,
                        'created_at' => $comment->created_at
                    ];
                }),
                'pagination' => [
                    'current_page' => $comments->currentPage(),
                    'last_page' => $comments->lastPage(),
                    'per_page' => $comments->perPage(),
                    'total' => $comments->total(),
                ],
            ];
        }
        return api_response(['story' => $formattedStory]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,mov,avi,flv',
            'cover' => 'nullable|file|mimes:jpg,jpeg,png',
            'title' => 'nullable',
            'who' => 'required|in:professor,seller',
        ]);

        $videoPath = null;

        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $videoPath = $video->storeAs('stories/video', $video->getClientOriginalName(), 'public');
        }

        $coverPath = null;

        if ($request->hasFile('cover')) {
            $cover = $request->file('cover');
            $coverPath = $cover->storeAs('stories/cover', $cover->getClientOriginalName(), 'public');
        }

        $user = auth()->user();
        $id = $user->id;
        $title = $user->getFullName();
        $coverPath = $coverPath ?? null;

        Story::create([
            'title' => $request->title ?? $title,
            'video' => $videoPath,
            'cover' => $coverPath,
            'user_id' => $id,
            'is_approved' => false,
            'who' => $request->who,
        ]);

        return api_response([], 'استوری با موفقیت ثبت شد و منتظر تأیید است.');
    }

    public function destroy(Story $story)
    {
        $user = auth()->user()->id;
        $story->where('user_id', $user)->where('id', $story->id)->delete();
        return api_response([], 'با موفقیت حذف شد');
    }


    public function toggleLike(Request $request, $id)
    {
        $group = Story::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return api_response([], 'برای لایک کردن لاگین کنید', 401);
        }

        $like = $user->likes()
            ->where('likeable_id', $group->id)
            ->where('likeable_type', Story::class)
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

    public function comment(Request $request, $id)
    {
        $request->validate([
            'body' => 'nullable|string|min:1|max:400',
        ], [], [
            'body' => 'توضیحات',
        ]);
        $groupClass = Story::findOrFail($id);
        $comment = $groupClass->comments()->create([
            'user_id' => auth()->user()->id,
            'body' => $request->body,

        ]);

        return api_response([
            'id' => $comment->id,
            'body' => $comment->body,
            'created_at' => $comment->created_at->diffForHumans(),
        ], 'نظر با موفقیت ثبت شد');
    }

}
