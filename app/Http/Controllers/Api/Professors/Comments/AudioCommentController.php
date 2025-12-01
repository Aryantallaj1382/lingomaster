<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\shop\AudioComment;
use Illuminate\Http\Request;

class AudioCommentController extends Controller
{
    public function index(Request $request, $productId)
    {
        $audioComments = AudioComment::with(['user', 'likes', 'dislikes'])
            ->where('product_id', $productId)
            ->latest()
            ->paginate();

        $userId = auth()->id();

        $formattedComments = $audioComments->getCollection()->map(function ($comment) use ($userId) {
            $isLike = $comment->likes->where('user_id', $userId)->isNotEmpty();
            $isDislike = $comment->dislikes->where('user_id', $userId)->isNotEmpty();

            return [
                'id' => $comment->id, // شناسه کامنت
                'audio_path' => url('https://api.lingomasters.ir/public/' . $comment->audio_path), // آدرس صوتی
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

        // به‌روزرسانی `audioComments` با داده‌های فرمت‌دهی‌شده
        $audioComments->setCollection($formattedComments);

        // بازگشت به همراه اطلاعات پیجینیشن
        return api_response($audioComments, 'کامنت صوتی', 200);
    }


    public function store(Request $request)
    {
        $request->validate([
            'audio' => 'required',
            'product_id' => 'required',
        ]);

        $audioPath = $request->file('audio')->store('audios', 'public');

        $audioComment = new AudioComment();
        $audioComment->audio_path = $audioPath;
        $audioComment->product_id = $request->input('product_id');
        $audioComment->save();




        return api_response($audioComment , 'کامنت صوتی با موفقیت ثبت شد');
    }
}
