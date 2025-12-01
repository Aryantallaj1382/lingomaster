<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\shop\Baner;
use App\Models\shop\Product;
use Illuminate\Http\Request;

class BanerController extends Controller
{
    public function index(Request $request)
    {
        $baners = Baner::with('images')->get()->map(function ($baner) {
            $firstImage = $baner->images->first();

            return [
                'id' => $baner->id,
                'title' => $baner->title,
                'price' => $baner->price,
                'images' => $firstImage ? api::api  . $firstImage->path : null,
            ];
        });

        $banersChunked = $baners->chunk(2);

        $orderedBaners = [];
        $orderCounter = 1;

        foreach ($banersChunked as $chunk) {
            $orderedBaners['order' . $orderCounter] = $chunk;
            $orderCounter++;
        }

        return api_response($orderedBaners, 'بنرها با موفقیت دسته‌بندی و با نام‌های مشخص بازگردانده شدند');
    }




    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $banner = new Baner();
        $banner->title = $product->title;
        $banner->price = $product->price;
        $banner->category = $product->category->title;
        $banner->save();

        if ($product->images()->exists()) {
            $productImage = $product->images()->first();

            $banner->images()->create([
                'path' => $productImage->path,
            ]);
        }

        return api_response();
    }




}
