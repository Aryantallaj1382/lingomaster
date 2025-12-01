<?php
namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\contacts\header_banner;
use App\Models\shop\Baner;
use App\Models\shop\Category;
use App\Models\shop\EducationalProduct;
use App\Models\shop\Product;
use App\Models\shop\Slaider;
use App\Models\shop\Story;
use App\Models\shop\VideoBaner;
use App\Models\SystemSetting;

// افزودن مدل اسلایدر

class MainPageController extends Controller
{
    public function index()
    {

        $categories = Category::where('id', '!=', 1)->get()
            ->map(function ($categories) {

                return [
                    'id'=>$categories->id,
                    'title'=>$categories->title,
                    'slug'=>$categories->slug,
                    'icon' => $categories ? 'https://api.lingomasters.ir/storage/'.$categories->icon : null,

                ];
            });

        $productsWithDiscount = Product::where('just_show' ,false)->exceptHelpProducts()->with('images', 'language', 'category')
            ->hasDiscount()
            ->take(3)
            ->get()
            ->map(function ($product) {

                return [
                    'title' => $product->title,
                    'price' => $product->default_price,
                    'off_price' => $product->default_discounted_price,
                    'discount_percentage' => $product->default_discount_percentage,
                    'discount_expiration' => $product->default_discount_expire_at,
                    'language' => $product->language,
                    'category' => $product->category->title ??null,
                    'image' => $product->first_image,
                    'selected_seller' => $product->selected_seller,
                    'is_like' => $product->is_liked,
                    'is_download' => $product->is_download,
                    'id' => $product->id,

                ];
            });


        $randomProducts = Product::where('just_show' ,false)->exceptHelpProducts()->select('title', 'category_id','id','is_download')
            ->with('images','language','category')
            ->inRandomOrder()
            ->take(10)
            ->get()
            ->map(function ($product) {


                return [
                    'title' => $product->title,
                    'price' => $product->default_price,
                    'off_price' => $product->default_discounted_price,
                    'discount_percentage' => $product->default_discount_percentage,
                    'discount_expiration' => $product->default_discount_expire_at,
                    'language' => $product->language,
                    'category' => $product->category->title ??null,
                    'id' => $product->id,
                    'image' => $product->first_image,
                    'selected_seller' => $product->selected_seller,
                    'is_like' => $product->is_liked,
                    'is_download' => $product->is_download,
                ];
            });

            $latestProducts = Product::where('just_show' ,false)->exceptHelpProducts()->select('title', 'category_id','id','is_download')
                ->with('images','language','category')
                ->latest()
                ->take(10)
                ->get()
                ->map(function ($product) {

                    return [
                        'title' => $product->title,
                        'price' => $product->default_price,
                        'off_price' => $product->default_discounted_price,
                        'discount_percentage' => $product->default_discount_percentage,
                        'discount_expiration' => $product->default_discount_expire_at,
                        'language' => $product->language,
                        'category' => $product->category->title ??null,
                        'id' => $product->id,
                        'image' => $product->first_image,
                        'selected_seller' => $product->selected_seller,
                        'is_like' => $product->is_liked,
                        'is_download' => $product->is_download,
                    ];
                });

        $bestSellingProducts = Product::where('just_show' ,false)->exceptHelpProducts()->select('title', 'category_id','id','is_download','is_download')
            ->with('images','language','category')
            ->inRandomOrder()
            ->take(10)
            ->get()
            ->map(function ($product) {

                return [
                    'title' => $product->title,
                    'price' => $product->default_price,
                    'off_price' => $product->default_discounted_price,
                    'discount_percentage' => $product->default_discount_percentage,
                    'discount_expiration' => $product->default_discount_expire_at,
                    'language' => $product->language,
                    'id' => $product->id,
                    'image' => $product->first_image,
                    'selected_seller' => $product->selected_seller,
                    'is_like' => $product->is_liked,
                    'is_download' => $product->is_download,
                ];
            });

        $latestEducationalProducts = Product::helpProducts()
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($product) {

                return [
                    'title' => $product->title,
                    'price' => $product->default_price,
                    'off_price' => $product->default_discounted_price,
                    'discount_percentage' => $product->default_discount_percentage,
                    'discount_expiration' => $product->default_discount_expire_at,
                    'language' => $product->language,
                    'id' => $product->id,
                    'image' => $product->first_image,
                    'selected_seller' => $product->selected_seller,
                    'is_like' => $product->is_liked,
                    'is_download' => $product->is_download,
                ];
            });

        $baners = Baner::get()->map(function ($baner) {

            return [
                'id' => $baner->id,
                'title' =>  $baner->product->title ??null,
                'category' => $baner->product->category->title ??null,
                'product_id' => $baner->product->id,
                'image' => $baner->product_image ?? null,
                'selected_seller' =>$baner->selected_seller,


            ];
        });
        $banersChunked = $baners->chunk(2);

        $orderedBaners = [];
        $orderCounter = 1;

        foreach ($banersChunked as $chunk) {
            $orderedBaners['order' . $orderCounter] = $chunk->values()->all();
            $orderCounter++;
        }
        $video_banners = VideoBaner::all()->map(function ($banner) {
            return [
                'path' => api::api . $banner->path,
//                'cover'=> $banner->covers(),
                'cover' =>  $banner->cover ? api::api  .  $banner->cover : null,


            ];
        });


        $sliders = Slaider::where('location','shop')->get()->map(function ($slider) {
            return [
                'id' => $slider->id,
                'link' => $slider->link,
                'tablet_image' => $slider->tablet_image,
                'laptop_image' => $slider->laptop_image,
                'mobile_image' => $slider->mobile_image,
            ];
        });


        $story = Story::where("is_approved", 1)
            ->where('who' , 'seller')
            ->latest()
            ->select('cover', 'id', 'title')
            ->get()
            ->map(function ($story) {
                return [
                    'id' => $story->id,
                    'title' => $story->title,
                    'cover' => $story->cover, // تبدیل مسیر کاور به URL کامل
                ];
            });




        $store_header_banner_link = SystemSetting::where('id', header_banner::header_banner_id)->value('name');
        $store_header_banner_image = SystemSetting::where('id', header_banner::header_banner_id)->value('value');


        return api_response([
            'discounted_products' => $productsWithDiscount,
            'random_products' => $randomProducts,
            'latest_products' => $latestProducts,
            'best_selling_products' => $bestSellingProducts,
            'latest_educational_products' => $latestEducationalProducts,
            'baners' => $orderedBaners,
            'video_banners' => $video_banners,
            'categories' => $categories,
            'sliders' => $sliders,
            'stories' => $story,
            'header_banner'=>[
                'link' => $store_header_banner_link,
                'image' => 'https://api.lingomasters.ir/'.$store_header_banner_image,
            ],
        ], ' با موفقیت بارگذاری شد');
    }
}
