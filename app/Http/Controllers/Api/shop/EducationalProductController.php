<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\StoreEducationalProductRequest;
use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\ProductView;
use App\Models\Seller\Seller;
use App\Models\shop\Category;
use App\Models\shop\EducationalProduct;
use App\Models\shop\Product;
use App\Models\shop\Publication;
use App\Models\shop\Subjects;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EducationalProductController extends Controller
{

    public function index(Request $request)
    {
        $query = Product::helpProducts()->with(['images', 'category'])
            ->filterByRequest($request)
            ->sortByRequest($request);

        $products = $query->paginate(12);

        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $product->images->isNotEmpty() ? api::api . $product->images->first()->path : null,
                'selected_seller' => $product->selected_seller,
                'category' => $product->category->title ?? 'نامشخص',
                'is_like' => $product->is_liked,
                'is_download' => $product->is_download,
            ];
        });

        return api_response($products);
    }

    public function GetFilter()
    {
        $category1 = Category::where('id', '=', 1)->get();

        $productIds = Product::pluck('id')->toArray();
        $minPrice = \DB::table('product_seller')
            ->whereIn('product_id', $productIds)
            ->selectRaw('MIN(COALESCE(discounted_price, price)) as min_price')
            ->value('min_price');

        $maxPrice = \DB::table('product_seller')
            ->whereIn('product_id', $productIds)
            ->selectRaw('MAX(COALESCE(discounted_price, price)) as max_price')
            ->value('max_price');

        $products = Product::all();
        $publicationIds = $products->pluck('publication_id')->unique();
        $ageGroupIds = $products->pluck('age_group_id')->unique();
        $subjectIds = $products->pluck('subject_id')->unique();
        $languageIds = $products->pluck('language_id')->unique();

        $publications = Publication::whereIn('id', $publicationIds)->get();
        $ageGroups = AgeGroup::whereIn('id', $ageGroupIds)->get();
        $subjects = Subjects::whereIn('id', $subjectIds)->get();
        $languages = Languages::whereIn('id', $languageIds)->get();

        $result = [
            'category' => $category1->map(fn($cat) => [
                'value' => $cat->id,
                'name' => $cat->title,
                'slug' => $cat->slug,
            ])->toArray(),
            'publication' => $publications->map(fn($publication) => [
                'value' => $publication->id,
                'name' => $publication->title,
            ])->toArray(),
            'age_group' => $ageGroups->map(fn($ageGroup) => [
                'value' => $ageGroup->id,
                'name' => $ageGroup->title,
            ])->toArray(),
            'subject' => $subjects->map(fn($subject) => [
                'value' => $subject->id,
                'name' => $subject->title,
            ])->toArray(),
            'language' => $languages->map(fn($language) => [
                'value' => $language->id,
                'name' => $language->title,
            ])->toArray(),
            'price_range' => [
                'min_price' => (int)$minPrice ?? 0,
                'max_price' => (int)$maxPrice ?? 0,
            ],
        ];

        return api_response($result, 'فیلتر با موفقیت ارسال شد');
    }


    public function show(Product $product)
    {
        $product->load([
            'images',
            'videos',
            'SampleImages',
            'language',
            'subject',
            'category',
            'age_group',
            'covers',
            'publication',
            'product_type',
            'sellers.seller'
        ]);
        if ($product->category_id != 1) {
            return api_response(null, 'محصول مورد نظر یافت نشد یا مجاز نیست.', 404);
        }

        $sellers = $product->sellers;

        $mainSeller = $sellers->where('seller_id', 1)->first();

        $cheapestSeller = $sellers->sortBy(function ($item) {
            return $item->discounted_price ?? $item->price;
        })->first();

        $mostExpensiveSeller = $sellers->sortByDesc(function ($item) {
            return $item->discounted_price ?? $item->price;
        })->first();

        $s = $sellers->map(function ($item) {
            return [
                'seller_id' => $item->id,
                'id' => $item->seller_id,
                'name' => optional($item->seller)->name,
                'price' => $item->price,
                'stock' => $item->quantity,
                'discount_percentage' => $item->discount_percentage,
                'discounted_price' => $item->discounted_price ?? $item->price,
                'discount_expire_at' => $item->discount_expire_at,

            ];
        });

        $imagePath = $product->images->isNotEmpty() ? api::api . $product->images->first()->path : null;
        $videoPath = $product->videos->isNotEmpty() ? api::api . $product->videos->first()->path : null;
        $covers = $product->covers->isNotEmpty() ? api::api . $product->covers->first()->path : null;

        $sampleImages = $product->SampleImages->map(function ($sampleImage) {
            return [
                'path' => api::api . $sampleImage->path,
                'id' => $sampleImage->id,
            ];
        })->toArray();


        $selectedSeller = $mainSeller ?? $cheapestSeller;
        if ($product->category->id == 33)
        {
            $r = 'دسته دوم';
        }
        else{$r = 'نو';}
        return api_response([
            'id' => $product->id,
            'title' => $product->title,
            'author' => $product->author,
            'Volume_number' => $product->Volume_number,
            'page_number' => $product->page_number,
            'language' => $product->language->title ?? null,
            'age_group' => $product->age_group->title ?? null,
            'subject' => $product->goal->title ?? null,
            'category' => $product->category->title ?? null,
            'rade'=> $r ,
            'year_of_publication' => $product->year_of_publication,
            'product_type' => $product->product_type->title ?? null,
            'Time_to_print' => $product->Time_to_print,
            'Dimensions' => $product->Dimensions,
            'publication' => $product->publication->title ?? null,
            'Paper_type' => $product->Paper_type,
            'Cover_type' => $product->Cover_type,
            'shabak_number' => $product->shabak_number,
            'fipa_number' => $product->fipa_number,
            'weight' => $product->weight,
            'price' => $selectedSeller?->price,
            'off_price' => $selectedSeller?->discounted_price ?? $selectedSeller?->price,
            'is_like' => $product->is_liked, 'category_slug' => $product->category->slug ?? null,
            'discount_percentage' => $selectedSeller?->discount_percentage,
            'discount_expiration' => $selectedSeller?->discount_expire_at,
            'image' => $imagePath,
            'video' => $videoPath,
            'video_covers' => $covers,
            'book_category' => $product->book_category,
            'audio_file' => $product->audio_file,
            'video_file' => $product->video_file,
            'video_accent' => $product->video_accent,
            'audio_accent' => $product->audio_accent,
            'sample_images' => $sampleImages,
            'is_download' => $product->is_download,
            'sellers' => $s,
            'selected_seller' => $product->selected_seller,
            'min_price' => $cheapestSeller?->discounted_price ?? $cheapestSeller?->price,
            'max_price' => $mostExpensiveSeller?->discounted_price ?? $mostExpensiveSeller?->price,
            'average_rate' => $product->average_rating,
            'rate_count' => $product->ratings_count,
            'rate' => $product->user_rating,

        ]);
    }



}
