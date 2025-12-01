<?php

namespace App\Http\Controllers\Api\seller;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\shop\Comment;
use App\Models\shop\Product;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class sellerCommentController extends Controller
{

    public function TextComments(Request $request)
    {
        $seller = Auth::user()->seller;   // فروشندهٔ لاگین‌شده
        $userId = Auth::id();

        /** @var \Illuminate\Support\Collection $comments */
        $comments = Comment::with([
            'commentable',      // خود کتاب
            'user.student',
            'likes',
            'dislikes',
        ])
            ->whereNotNull('body')
            ->whereHasMorph(
                'commentable',
                [Product::class],
                fn($q) => $q->whereHas('sellers', fn($q) => $q->where('seller_id', $seller->id))
            )
            ->latest()
            ->get();

        $grouped = $comments
            ->groupBy('commentable_id')
            ->map(function ($group) use ($userId) {
                /** @var Product $product */
                $product = $group->first()->commentable;

                $bookInfo = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'image' => $product->first_image,
                    'category' => $product->category->title ?? null,
                ];
                $totalComments = $group->count();

                $bookComments = $group->take(5)->map(function ($comment) use ($userId) {
                    return [
                        'id' => $comment->id,
                        'name' => optional($comment->user)->full_name(),
                        'profile' => optional($comment->user)->profile,
                        'content' => $comment->body,
                        'created_at' => $comment->created_at,
                        'is_like' => $userId ? $comment->likes->where('user_id', $userId)->isNotEmpty() : false,
                        'is_dislike' => $userId ? $comment->dislikes->where('user_id', $userId)->isNotEmpty() : false,
                    ];
                })->values();
                return array_merge($bookInfo, [
                    'has_more' => $totalComments > 5,   // اگر بیشتر از ۵ کامنت بود، true

                    'comments' => $bookComments,
                ]);
            })
            ->values();

        return api_response($grouped);
    }


    public function VideoComments(Request $request)
    {
        $seller = Auth::user()->seller;   // فروشندهٔ لاگین‌شده
        $userId = Auth::id();

        /** @var \Illuminate\Support\Collection $comments */
        $comments = Comment::with([
            'commentable',      // خود کتاب
            'user.student',
            'likes',
            'dislikes',
        ])
            ->whereNotNull('video_url')
            ->whereHasMorph(
                'commentable',
                [Product::class],
                fn($q) => $q->whereHas('sellers', fn($q) => $q->where('seller_id', $seller->id))
            )
            ->latest()
            ->get();

        $grouped = $comments
            ->groupBy('commentable_id')
            ->map(function ($group) use ($userId) {
                /** @var Product $product */
                $product = $group->first()->commentable;

                $bookInfo = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'image' => $product->first_image,
                    'category' => $product->category->title ?? null,
                ];
                $totalComments = $group->count();

                $bookComments = $group->take(5)->map(function ($comment) use ($userId) {
                    return [
                        'id' => $comment->id,
                        'name' => optional($comment->user)->full_name(),
                        'profile' => optional($comment->user)->profile,
                        'video_url' => $comment->video_url ? api::api . $comment->video_url : null,
                        'created_at' => $comment->created_at,
                        'is_like' => $userId ? $comment->likes->where('user_id', $userId)->isNotEmpty() : false,
                        'is_dislike' => $userId ? $comment->dislikes->where('user_id', $userId)->isNotEmpty() : false,
                    ];
                })->values();

                return array_merge($bookInfo, [
                    'has_more' => $totalComments > 5,   // اگر بیشتر از ۵ کامنت بود، true

                    'comments' => $bookComments,
                ]);
            })
            ->values();

        return api_response($grouped);
    }

    public function VoiceComments(Request $request)
    {
        $seller = Auth::user()->seller;   // فروشندهٔ لاگین‌شده
        $userId = Auth::id();

        /** @var \Illuminate\Support\Collection $comments */
        $comments = Comment::with([
            'commentable',      // خود کتاب
            'user.student',
            'likes',
            'dislikes',
        ])
            ->whereNotNull('voice_url')
            ->whereHasMorph(
                'commentable',
                [Product::class],
                fn($q) => $q->whereHas('sellers', fn($q) => $q->where('seller_id', $seller->id))
            )
            ->latest()
            ->get();

        $grouped = $comments
            ->groupBy('commentable_id')
            ->map(function ($group) use ($userId) {
                /** @var Product $product */
                $product = $group->first()->commentable;

                $bookInfo = [
                    'id' => $product->id,
                    'title' => $product->title,
                    'image' => $product->first_image,
                    'category' => $product->category->title ?? null,
                ];
                $totalComments = $group->count();

                $bookComments = $group->take(5)->map(function ($comment) use ($userId) {
                    return [
                        'id' => $comment->id,
                        'name' => optional($comment->user)->full_name(),
                        'profile' => optional($comment->user)->profile,
                        'voice_url' => $comment->voice_url ? api::api . $comment->voice_url : null,
                        'created_at' => $comment->created_at,
                        'is_like' => $userId ? $comment->likes->where('user_id', $userId)->isNotEmpty() : false,
                        'is_dislike' => $userId ? $comment->dislikes->where('user_id', $userId)->isNotEmpty() : false,
                    ];
                })->values();

                return array_merge($bookInfo, [
                    'has_more' => $totalComments > 5,   // اگر بیشتر از ۵ کامنت بود، true

                    'comments' => $bookComments,
                ]);
            })
            ->values();

        return api_response($grouped);
    }


    //////////////////////////
    private function fetchProductComments(
        Product $product,
        ?string $contentColumn = null,   // 'video_url' یا 'voice_url' یا null برای متن
        int     $perPage = 10
    ): LengthAwarePaginator
    {
        $seller = Auth::user()->seller;   // فروشندهٔ لاگین‌شده
        $userId = Auth::id();

        // بررسی شود که واقعاً این محصول برای همین فروشنده است
        abort_if(!$product->sellers()->where('seller_id', $seller->id)->exists(), 403);

        $commentsQuery = Comment::with([
            'user.student',
            'likes',
            'dislikes',
        ])
            ->where('commentable_type', Product::class)
            ->where('commentable_id', $product->id);

        // فیلتر نوع محتوا
        if ($contentColumn) {
            $commentsQuery->whereNotNull($contentColumn);
        } else {
            $commentsQuery->whereNotNull('body');
        }

        $commentsQuery->latest();

        /** @var LengthAwarePaginator $paginated */
        $paginated = $commentsQuery->paginate($perPage)->through(function ($comment) use ($userId, $contentColumn) {
            return [
                'id' => $comment->id,
                'name' => optional($comment->user)->full_name(),
                'profile' => optional($comment->user)->profile,
                // ستون محتوا (متن/ویدئو/صوت)
                'content' => $contentColumn ? null : $comment->body,
                'video_url' => $contentColumn === 'video_url'
                    ? ($comment->video_url ? api::api . $comment->video_url : null)
                    : null,
                'voice_url' => $contentColumn === 'voice_url'
                    ? ($comment->voice_url ? api::api . $comment->voice_url : null)
                    : null,
                'created_at' => $comment->created_at,
                'is_like' => $userId ? $comment->likes->where('user_id', $userId)->isNotEmpty() : false,
                'is_dislike' => $userId ? $comment->dislikes->where('user_id', $userId)->isNotEmpty() : false,
            ];
        });

        return $paginated;
    }

    /* ---------- متدهای show ---------- */

    public function showTextComments(Request $request, Product $product)
    {
        $comments = $this->fetchProductComments($product, null, $request->get('per_page', 10));

        return api_response([

            'id' => $product->id,
            'title' => $product->title,
            'image' => $product->first_image,
            'category' => $product->category->title ?? null,

            'comments' => $comments,               // شامل داده + لینک‌های صفحه‌بندی
        ]);
    }

    public function showVideoComments(Request $request, Product $product)
    {
        $comments = $this->fetchProductComments($product, 'video_url', $request->get('per_page', 10));

        return api_response([

            'id' => $product->id,
            'title' => $product->title,
            'image' => $product->first_image,
            'category' => $product->category->title ?? null,

            'comments' => $comments,               // شامل داده + لینک‌های صفحه‌بندی
        ]);
    }

    public function showVoiceComments(Request $request, Product $product)
    {
        $comments = $this->fetchProductComments($product, 'voice_url', $request->get('per_page', 10));

        return api_response([

            'id' => $product->id,
            'title' => $product->title,
            'image' => $product->first_image,
            'category' => $product->category->title ?? null,

            'comments' => $comments,               // شامل داده + لینک‌های صفحه‌بندی
        ]);
    }


}
