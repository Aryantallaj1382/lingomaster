<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\shop\TextComment;
use Illuminate\Http\Request;

class TextCommentController extends Controller
{

    public function index(Request $request, $productId)
    {
        $userId = auth()->id(); // شناسه کاربر فعلی (اگر کاربر لاگین کرده باشد)

        $textComments = TextComment::with(['user', 'comment.likes', 'comment.dislikes'])
            ->where('product_id', $productId)
            ->latest()
            ->paginate(10);

        $formattedComments = $textComments->getCollection()->map(function ($comment) use ($userId) {
            $commentModel = $comment->comment;
            $isLike = false;
            $isDislike = false;
            $likesCount = 0;
            $dislikesCount = 0;

            if ($commentModel) {
                // بررسی اینکه آیا کاربر فعلی این کامنت را لایک یا دیس‌لایک کرده است
                $isLike = $commentModel->likes->where('user_id', $userId)->isNotEmpty();
                $isDislike = $commentModel->dislikes->where('user_id', $userId)->isNotEmpty();
                $likesCount = $commentModel->likes->count();
                $dislikesCount = $commentModel->dislikes->count();
            }

            return [
                'id' => $comment->id,
                'content' => $comment->content,
                'created_at' => $comment->created_at->toDateTimeString(),
                'user' => [
                    'user_name' => $comment->user->first_name ?? null,
                    'image' => $comment->user->profile_image ?? null,
                ],
                'likes_count' => $likesCount,
                'dislikes_count' => $dislikesCount,
                'is_like' => $isLike,
                'is_dislike' => $isDislike,
            ];
        });

        $textComments->setCollection($formattedComments);

        return api_response($textComments, 'Comments retrieved successfully', 200);
    }




    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'product_id' => 'required|integer',

        ]);

        $textComment = new TextComment();
        $textComment->content = $request->input('content');
        $textComment->product_id = $request->input('product_id');
        $textComment->save();


        return api_response($textComment,'کامنت با موفقیت ثبت شد');
    }

    public function toggleLikeDislike(Request $request, $commentId)
    {
        $user = auth()->user();
        $comment = TextComment::findOrFail($commentId);

        $request->validate([
            'type' => 'required|in:like,dislike',
        ]);

        if ($request->input('type') === 'like') {
            $comment->dislikes()->where('user_id', $user->id)->delete();

            $existingLike = $comment->likes()->where('user_id', $user->id)->first();
            if ($existingLike) {
                $existingLike->delete();
                return api_response([], 'لایک حذف شد');
            }

            $comment->likes()->create(['user_id' => $user->id]);
            return api_response([], 'کامنت لایک شد');
        } elseif ($request->input('type') === 'dislike') {
            $comment->likes()->where('user_id', $user->id)->delete();
            $existingDislike = $comment->dislikes()->where('user_id', $user->id)->first();
            if ($existingDislike) {
                $existingDislike->delete();
                return api_response([], 'دیس‌لایک حذف شد');
            }

            $comment->dislikes()->create(['user_id' => $user->id]);
            return api_response([], 'کامنت دیس‌لایک شد');
        }

        return api_response([], 'عملیات نامعتبر', 400);
    }


}
