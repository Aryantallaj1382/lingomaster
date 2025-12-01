<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\Professors\Professor;
use App\Models\shop\Comment;
use App\Models\shop\VideoComment;
use Illuminate\Http\Request;

class VideoCommentController extends Controller
{
    public function index(Request $request, $productId)
    {
        // فقط کامنت‌های ویدیویی را فیلتر می‌کنیم
        $videoComments = Comment::with(['commentable', 'likes', 'dislikes', 'user'])
            ->where('commentable_id', $productId)
            ->where('commentable_type', Professor::class) // مدل والد (محصول)
            ->where('type', 'video') // فقط نوع ویدیویی
            ->latest()
            ->paginate($request->per_page ?? 10);

        $userId = auth()->id(); // آی‌دی کاربر احراز هویت شده

        // فرمت‌دهی کامنت‌ها برای پاسخ
        $formattedComments = $videoComments->getCollection()->map(function ($comment) use ($userId) {
            $isLike = $comment->likes->where('user_id', $userId)->isNotEmpty(); // آیا کاربر لایک کرده؟
            $isDislike = $comment->dislikes->where('user_id', $userId)->isNotEmpty(); // آیا کاربر دیس‌لایک کرده؟

            return [
                'id' => $comment->id, // شناسه کامنت
                'video_path' => url('https://api.lingomasters.ir/public/' . $comment->media_url), // آدرس ویدیو
                'created_at' => $comment->created_at->toDateTimeString(), // تاریخ ایجاد
                'user' => [
                    'user_name' => $comment->user->first_name ?? null, // نام کاربر
                    'image' => $comment->user->profile_image ? url('https://api.lingomasters.ir/public/' . $comment->user->profile_image) : null, // تصویر کاربر
                ],
                'likes_count' => $comment->likes->count(), // تعداد لایک‌ها
                'dislikes_count' => $comment->dislikes->count(), // تعداد دیس‌لایک‌ها
                'is_like' => $isLike, // وضعیت لایک کاربر
                'is_dislike' => $isDislike, // وضعیت دیس‌لایک کاربر
            ];
        });

        // اعمال کامنت‌های فرمت‌شده به صفحه‌بندی
        $videoComments->setCollection($formattedComments);

        // بازگرداندن پاسخ
        return api_response($videoComments, 'لیست کامنت‌های ویدیویی', 200);
    }


    public function store(Request $request)
    {
        // اعتبارسنجی ورودی‌ها
        $request->validate([
            'video' => 'required|mimes:mp4,mov,ogg,qt|max:20480', // حداکثر 20 مگابایت
            'product_id' => 'required|exists:products,id', // اطمینان از وجود محصول
        ]);

        // آپلود فایل ویدئویی
        $originalName = $request->file('video')->getClientOriginalName(); // نام اصلی فایل
        $destinationPath = public_path('uploads/videos'); // مسیر ذخیره‌سازی
        $fileName = time() . '_' . $originalName; // نام یکتا برای فایل
        $request->file('video')->move($destinationPath, $fileName);

        $videoComment = new Comment();
        $videoComment->commentable_id = $request->input('product_id');
        $videoComment->commentable_type = Professor::class;
        $videoComment->type = 'video'; // نوع کامنت
        $videoComment->media_url = 'uploads/videos/' . $fileName;
        $videoComment->user_id = auth()->id();
        $videoComment->save();

        return api_response($videoComment, 'کامنت ویدئویی با موفقیت ثبت شد', 201);
    }



}
