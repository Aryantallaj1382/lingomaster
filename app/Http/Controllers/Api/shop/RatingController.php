<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\RatingRequest;
use App\Models\Rating;
use App\Models\shop\Product;

class RatingController extends Controller
{
    /**
     * Store a rating for a product.
     *
     * @param  RatingRequest  $request
     * @param  Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(RatingRequest $request, Product $product)
    {
        $rating = new Rating();
        $rating->user_id = auth()->id();
        $rating->rating = $request->input('rating');
        $rating->rateable()->associate($product);
        $rating->save();

        return api_response(null, 'امتیاز با موفقیت ثبت شد');
    }

    /**
     * Get ratings for a product.
     *
     * @param  Product  $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Product $product)
    {
        $ratings = $product->ratings()->with('user')->get();

        return api_response([
            'ratings' => $ratings,
        ]);
    }
}
