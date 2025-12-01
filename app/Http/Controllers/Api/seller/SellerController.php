<?php

namespace App\Http\Controllers\Api\seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningGoal;
use App\Models\Seller\ProductSeller;
use App\Models\shop\Category;
use App\Models\shop\Product;
use App\Models\shop\Publication;
use Illuminate\Http\Request; // ✅ درست
use Illuminate\Support\Facades\Storage;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $sellerId = auth()->user()->seller->id;

        $query = ProductSeller::where('seller_id', $sellerId);
        if ($request->filled('category')) {
            $query->whereRelation('product', 'category_id', '=', $request->category);
        }
        if ($request->filled('title')) {
            $query->whereRelation('product','title', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('id')) {
            $query->whereRelation('product','id', '=', $request->id);
        }
        if ($request->filled('status')) {
            $query->whereRelation('product', 'status', '=', $request->status);
        }
        if ($request->filled('date')) {
            $query->where('created_at', '=', $request->date);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        if ($request->filled('quantity')) {
            if ($request->quantity == true) {
                $query->where('quantity', '>', 0);
            }
            if ($request->quantity == false) {
                $query->where('quantity', '=', 0);
            }
        }

        $p = $query->paginate();



        $p->getCollection()->transform(function ($product) {
                return [
                    'status' => $product->product->status ?? null,
                    'id' => $product->product_id,
                    'title' => $product->product->title ?? null,
                    'price' => $product->price,
                    'off_price' => $product->discounted_price,
                    'quantity' => $product->quantity,
                    'is_active' => $product->is_active,
                    'Volume_number' => $product->product->Volume_number ?? null,
                    'category' => $product->product->category->title ?? null,
                    'sell_data' => $product->sales_data ?? null,
                ];
            });
        return api_response($p);
    }

    public function store(StoreBookRequest $request)
    {
        $seller = auth()->user()->seller->id;
        $product = Product::create([
            'title' => $request->title,
            'Dimensions' => $request->size,
            'category_id' => $request->category_id,
            'author' => $request->author,
            'Volume_number' => $request->volume_number,
            'page_number' => $request->page_number,
            'language_id' => $request->language_id,
            'publication_id' => $request->publication_id,
            'year_of_publication' => $request->years_of_publication,
            'age_group_id' => $request->age_group_id,
            'language_levels_id' => $request->level_id,
            'learning_subgoal_id' => $request->subject_id,
            'Time_to_print' => $request->time_of_print,
            'accent' => $request->accent,
            'Paper_type' => $request->paper_type,
            'Cover_type' => $request->cover_type,
            'fipa_number' => $request->fipa_number,
            'shabak_number' => $request->shabak_number,
            'weight' => $request->weight,
            'is_download' => $request->is_downlad,
            'status' => 'pending',


        ]);
        ProductSeller::create([
            'product_id' => $product->id,
            'seller_id' => $seller,
            'price' => $request->price,
            'quantity' => $request->quantity,
            'discounted_price' => $request->discounted_price ??$request->price,
            'is_active' => $request->is_active,

        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_url = $image->storeAs('/images/product/' . $product->title, $image->getClientOriginalName(), 'public');
            $product->images()->create(['path' => $image_url]);
        }
        if ($request->hasFile('cover')) {
            $image = $request->file('cover');
            $image_url = $image->storeAs('/images/cover/' . $product->title, $image->getClientOriginalName(), 'public');
            $product->covers()->create(['path' => $image_url]);
        }

        if ($request->hasFile('video')) {
            $video = $request->file('video');
            $image_url = $video->storeAs('/video/product/' . $product->title, $video->getClientOriginalName(), 'public');
            $product->videos()->create(['path' => $image_url]);
        }
        if ($request->hasFile('sample_images')) {
            foreach ($request->file('sample_images') as $sampleImage) {
                $image_url = $sampleImage->storeAs(
                    '/images/sample_image/' . $product->title, $sampleImage->getClientOriginalName(), 'public'
                );
                $product->SampleImages()->create(['path' => $image_url]);
            }

        }
        return api_response([], 'محصول با موفقیت ثبت شد و بعد از تایید ادمین به نمایش گذاشته میشود');

    }

    public function update(Request $request , $id)
    {
        $product = ProductSeller::where('seller_id' , auth()->user()->seller->id)->where('product_id',$id)->first();
        if (!$product) {
            return api_response([], 'محصول یافت نشد', 404);
        }
        if ($product->price == $product->discounted_price)
        {
            $product->update([
                'price' => $request->price,
                'discounted_price' => $request->price,
                'quantity' => $request->quantity,
            ]);
        }
        else{
            $product->update([
                'price' => $request->price,
                'quantity' => $request->quantity,
            ]);
        }

        if ($request->discount == 1) {
            $product->update([
                'discounted_price' => $request->price,
            ]);
        }

        return api_response([], 'محصول با موفقیت ویرایش شد ');
    }

    public function delete( $id)
    {
        $product = ProductSeller::where('seller_id' , auth()->user()->seller->id)->where('product_id',$id)->first();

        if (!$product) {
            return api_response([], 'محصول پیدا نشد', 404);
        }

        $product->delete();

        return api_response([], 'محصول و فایل‌های مربوطه حذف شدند');
    }

    public function active($id)
    {
        $product = ProductSeller::where('seller_id' , auth()->user()->seller->id)->where('product_id',$id)->first();
        if (!$product) {
            return api_response([], 'محصول یافت نشد', 404);
        }
        if ($product->is_active == 1)
        {
            $product->update([
                'is_active' => 0
            ]);
            return api_response([], 'محصول غیر فعال شد');

        }
        else{
            $product->update([
                'is_active' => 1
            ]);
            return api_response([], 'محصول فعال شد');

        }
    }

    public function seen_again(Request $request, $id)
    {
        $product = Product::find($id);
        $product->update([
            'status' => 'pending'
        ]);
        return api_response([], 'در خواست بازبینی مجدد با موفیقت داده شد');

    }

    public function initial_data()
    {
        $publication = Publication::all();
        $language = Languages::all();
        $subject = LearningGoal::with('subgoals') // بارگذاری همه‌ی subgoals مربوط به هر goal
        ->get()
            ->map(function($goal) {
                return [
                    'goal_id' => $goal->id,
                    'goal_title' => $goal->title,
                    'subgoals' => $goal->subgoals->map(fn($subgoal) => [
                        'id' => $subgoal->id,
                        'title' => $subgoal->title,
                    ])->values(),
                ];
            })
            ->toArray();
        $age= AgeGroup::all();
        $level = LanguageLevel::all();
        return api_response([
            'age_groups' => $age,
            'languages' => $language,
            'subjects' => $subject,
            'levels' => $level,
            'publications' => $publication,

        ]);

    }

    public function check(Request $request)
    {
        $shabak = $request->shabak_number;
        $fipa = $request->fipa_number;
        $id = $request->productId;
        $name = $request->name;

        // اگر هیچ داده‌ای ارسال نشده بود، مستقیماً پاسخ بده
        if (empty($shabak) && empty($fipa) && empty($id) && empty($name)) {
            return api_response([
                'is_exist' => false,
                'shabak_number' => $shabak,
                'fipa_number' => $fipa,
            ]);
        }
        if (!empty($shabak)) {
            $product = Product::where('shabak_number', $shabak)->first();
        } elseif (!empty($fipa)) {
            $product = Product::where('fipa_number', $fipa)->first();
        } elseif (!empty($id)) {
            $product = Product::where('id', $id)->first();
        }  elseif (!empty($name)) {
            $product = Product::where('title','like', '%'.$name.'%')->first();
        } else {
            $product = null;
        }

        if (!$product) {
            return api_response([
                'is_exist' => false,
                'shabak_number' => $shabak,
                'fipa_number' => $fipa,
            ] , 'محصول وجود ندارد',
);
        }

        $sellers = $product->sellers;

        $cheapest = $sellers->sortBy(fn($seller) => $seller->discounted_price ?? $seller->price)->first();
        $mostExpensive = $sellers->sortByDesc(fn($seller) => $seller->discounted_price ?? $seller->price)->first();

        return api_response([
            'is_exist' => true,
            'name' => $product->title,
            'id' => $product->id,
            'image' => $product->first_image,
            'shabak_number' => $product->shabak_number,
            'fipa_number' => $product->fipa_number,
            'min_price' => $cheapest?->discounted_price ?? $cheapest?->price,
            'max_price' => $mostExpensive?->discounted_price ?? $mostExpensive?->price,
        ],'محصول وجود دارد');
    }

    public function search(Request $request)
    {
        $search = $request->moo;
        $products = Product::with('category')
            ->where('title', 'like', '%' . $search . '%')
            ->get()
            ->groupBy('category_id');

        $data = $products->map(function ($items, $categoryId) {
            $category = $items->first()->category;
            return [
                'id' => $categoryId,
                'title' => $category->title,
                'products' => $items->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'title' => $product->title,
                        'image' => $product->first_image,
                    ];
                })->values(),
            ];
        })->values();

        return api_response($data);

    }

    public function exist_product(Request $request)
    {
        $request->validate([
            'id' => 'exists:products,id',
            'price' => 'required',
            'is_active' => 'required',
            'quantity' => 'required',

        ]);
//        $seller_id = auth()->user()->seller->id;
        ProductSeller::create([
            'product_id' => $request->id,
            'seller_id' => 1,
            'price' => $request->price,
            'is_active' => $request->is_active,
            'quantity' => $request->quantity,
            'discounted_price' => $request->price,
        ]);

    return response()->json([
        'message' => 'محصول ثبت شد'
    ]);
    }

    public function category(Request $request)
    {
        $input = $request->type;
        $categories = null ;
        if ($input == 1){
            $categories = Category::get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'title' => $category->title,
                        'slug' => $category->slug,
                        'is_physical' => $category->is_physical,
                        'icon' => $category ? api::api  . $category->icon : null,
                    ];
                });
        }
        if ($input == 0){
            $categories = Category::where('id', '!=' , 10)->where('id', '!=' , 33)->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'title' => $category->title,
                        'slug' => $category->slug,
                        'is_physical' => $category->is_physical,
                        'icon' => $category ? api::api  . $category->icon : null,
                    ];
                });
        }

        return api_response($categories);
    }
}
