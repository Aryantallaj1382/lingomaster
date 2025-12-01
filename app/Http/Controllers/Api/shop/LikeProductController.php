<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\shop\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeProductController extends Controller
{
    public function toggleLike(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $user = Auth::user();

        if ($user) {
            $like = $user->likes()->where('likeable_id', $product->id)->where('likeable_type', Product::class)->first();

            if ($like) {
                $like->delete();
                return api_response([], 'لایک حذف شد');
            }

            $user->likes()->create([
                'likeable_id' => $product->id,
                'likeable_type' => Product::class,
            ]);

            return api_response([], 'محصول لایک شد');
        }

        return api_response([], 'برای لایک کردن لاگین کنید');
    }

}
