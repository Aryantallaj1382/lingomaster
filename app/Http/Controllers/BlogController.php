<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\contacts\api;
use App\Models\shop\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Reverb\Loggers\Log;

class BlogController extends Controller
{
    public function toggleLike(Request $request, $id)
    {
        $group = Blog::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return api_response([], 'برای لایک کردن لاگین کنید', 401);
        }

        $like = $user->likes()
            ->where('likeable_id', $group->id)
            ->where('likeable_type', Blog::class)
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
    public function index(Request $request)
    {
        $status = $request->query('status');
        $user = auth()->id();
        $blogs = Blog::select('id', 'title', 'image', 'status', 'created_at')->latest()
        ->where('status',$status)
            ->where('user_id',$user)
            ->paginate();


        return api_response($blogs);
    }
    public function index_a(Request $request)
    {

        $blogs = Blog::select('id', 'title', 'image', 'status', 'created_at')->latest()
            ->where('status','approved')
            ->paginate();


        return api_response($blogs);
    }

    public function store(Request $request)
    {
        $user = Auth::id();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'nullable', // حداکثر ۵ مگابایت
            'tags' => 'nullable|array',
            'time_for_read' => 'nullable|string',
            'about_blog' => 'nullable|string',
            'text' => ['nullable', function ($attribute, $value, $fail) {
                if (strlen($value) > 15728640) { // 15MB
                    $fail('متن نباید بیشتر از ۱۵ مگابایت باشد.');
                }
            }],
            ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('image/blog', 'public');
        }

        $blog = Blog::create([
            'user_id' => $user,
            'title' => $data['title'],
            'image' => $imagePath,
            'tags' => $data['tags'],
            'time_for_read' => $data['time_for_read'],
            'about_blog' => $data['about_blog'],
            'text' => $data['text'],
        ]);

        return api_response($blog);
    }

    public function show($id)
    {
        $blog = Blog::where('id', $id)
            ->withCount('likes')
            ->firstOrFail();

        $blog->increment('view');

        $user = auth()->user();
        $isLiked = $user ? $blog->isLikedByUser($user->id) : 0;

        $blog->is_like = $isLiked;

        return api_response($blog);
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'image' => 'nullable|file|image',
            'tags' => 'nullable|array',
            'time_for_read' => 'nullable|string',
            'about_blog' => 'nullable|string',
            'text' => 'nullable|string',
            'status' => 'in:pending,approved,rejected',
        ]);
        if ($request->hasFile('image')) {
            if ($blog->image && \Storage::disk('public')->exists($blog->image)) {
                \Storage::disk('public')->delete($blog->image);
            }

            $imagePath = $request->file('image')->store('image/blog', 'public');
            $data['image'] = $imagePath;
        }

        $blog->update($data);
        return api_response($blog);
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();
        return api_response(['message' => 'Blog deleted successfully.']);
    }
    /// برای سایت اصلی
    ///
    public function main()
    {
        $blogs = Blog::select('id', 'title', 'image', 'status', 'created_at')->latest()
            ->paginate();

        return api_response($blogs);
    }

    public function comment(Request $request, $id)
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

        $blog = Blog::findOrFail($id);

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
        $comment = $blog->comments()->create([
            'user_id' => Auth::id(),
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

        $blog = Blog::findOrFail($groupClassId);



        $query = $blog->comments()->with([
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
                'video_url' => $comment->video_url ? asset($comment->video_url) : null,
                'voice_url' => $comment->voice_url ? asset($comment->voice_url) : null,
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

}
