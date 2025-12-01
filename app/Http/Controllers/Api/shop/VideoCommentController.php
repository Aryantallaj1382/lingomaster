<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\shop\VideoComment;
use Illuminate\Http\Request;

class VideoCommentController extends Controller
{
    public function index(Request $request, $productId)
    {
        $videoComments = VideoComment::with(['user', 'likes', 'dislikes'])
            ->where('product_id', $productId)
            ->latest()
            ->paginate();

        $userId = auth()->id();

        $formattedComments = $videoComments->getCollection()->map(function ($comment) use ($userId) {
            $isLike = $comment->likes->where('user_id', $userId)->isNotEmpty();
            $isDislike = $comment->dislikes->where('user_id', $userId)->isNotEmpty();

            return [
                'id' => $comment->id, // شناسه کامنت
                'video_path' => $comment->video_path ? api::api . $comment->video_path : null, // آدرس ویدیو
                'created_at' => $comment->created_at->toDateTimeString(), // تاریخ ایجاد
                'user' => [
                    'user_name' => $comment->user->first_name ?? null, // نام کاربر
                    'image' => $comment->user->profile_image ?? null, // تصویر کاربر
                ],
                'likes_count' => $comment->likes->count(), // تعداد لایک‌ها
                'dislikes_count' => $comment->dislikes->count(), // تعداد دیس‌لایک‌ها
                'is_like' => $isLike, // وضعیت لایک کاربر
                'is_dislike' => $isDislike, // وضعیت دیس‌لایک کاربر
            ];
        });

        $videoComments->setCollection($formattedComments);

        return api_response($videoComments, '', 200);
    }


    public function store(Request $request)
    {
        $request->validate([
            'video' => 'required|mimes:mp4,mov,ogg,qt|max:20480',
            'product_id' => 'required|exists:products,id',
        ]);
        $originalName = $request->file('video')->getClientOriginalName();

        $destinationPath = public_path('video');
        $request->file('video')->move($destinationPath, $originalName);

        $videoComment = new VideoComment();
        $videoComment->video_path = 'video/' . $originalName;
        $videoComment->product_id = $request->input('product_id');
        $videoComment->save();

        return api_response($videoComment, 'کامنت ویدئویی با موفقیت ثبت شد');
    }


}
