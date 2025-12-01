<?php

namespace App\Http\Controllers;

use App\Models\contacts\api;
use App\Models\Professors\BookLearning;
use App\Models\shop\Product;
use Illuminate\Http\Request;

class LibraryCotroller extends Controller
{
    public function index(Request $request)
    {
        $lang = $request->input('language');
        $products = Product::where('status' , 'approved')->where('language_id' , $lang)->paginate();
        $products->getCollection()->transform(function ($product) {
            $prof = BookLearning::where('product_id' , $product->id)->where('status' , 'approved')->get();
            return [
                'image' => $product->images->isNotEmpty() ? api::api . $product->images->first()->path : null,
                'title' => $product->title,
                'id' => $product->id,
                'file' => $product->file ? api::api . $product->file : null,
                'language' => $product->language->title,
                'subject' => $product->goal->title,
                'category' =>$product->category->title,
                'Volume_number' =>$product->Volume_number,
                'professor' => $prof->map(function($prof){
                    return api::api.$prof->professors->profile;
                }),

            ];

        });

        return api_response($products);
    }
    public function show(Request $request , $id)
    {
        $product = Product::find($id);
        $prof = BookLearning::where('product_id' , $product->id)->where('status' , 'approved')->get();
        return api_response([
            'image' => $product->images->isNotEmpty() ? api::api . $product->images->first()->path : null,
            'title' => $product->title,
            'language' => $product->language->title,
            'subject' => $product->goal->title .'('. $product->goal->goal->timestamps.')' ,
            'Volume_number' =>$product->Volume_number,
            'professor' => $prof->map(function($prof){
                return api::api.$prof->professors->profile;
            }),
            'publication' => $product->publication->title,
            'author'=> $product->author,
            'Time_to_print'=> $product->Time_to_print,
            'price' => $product->default_price,
            'category' =>$product->category->title,
            'is_download' => $product->is_download,
            'age_group' => $product->age_group->title,
            'language_level' => $product->language_level->title,
            'id' =>$product->id,
        ]);

    }
}
