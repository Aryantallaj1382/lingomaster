<?php

namespace App\Http\Controllers\Api\seller;

use App\Http\Controllers\Controller;
use App\Models\DiscountCodeShop;
use App\Models\Seller\ProductSeller;
use Illuminate\Http\Request;

class SellerDiscountController extends Controller
{
    public function index(Request $request)
    {
        $seller = auth()->user()->seller;

        $query = ProductSeller::where('seller_id', $seller->id)
            ->whereColumn('discounted_price' , '<' , 'price')
            ->with(['product.category', 'product.language']);
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $products = $query->paginate();
        $startIndex = ($products->currentPage() - 1) * $products->perPage() + 1;

        $products->getCollection()->transform(function ($productSeller, $index) use ($startIndex) {
            $product = $productSeller->product;
            $dis = $productSeller->discounted_price ? (($productSeller->price - $productSeller->discounted_price) / $productSeller->price) * 100 : 0;
            return [
                'row' => $startIndex + $index,
                'id' => $product->id,
                'product' => (string)$productSeller->id,
                'off_price' => $productSeller->discounted_price,
                'price' => $productSeller->price,
                'expire_at' => $productSeller->discount_expire_at,
                'off_type' => $productSeller->off_type,
                'image' => $product->first_image,
                'discount' => round($dis),
                'category' => (string)$product->category->id ?? null,
                'category_title' => $product->category->title ?? null,
                'title' => $product->title,
                'flag' => $product->language->flag ?? null,
            ];
        });

        return api_response($products);
    }

    public function pro(Request $request)
    {
        $seller = auth()->user()->seller;

        $query = ProductSeller::where('seller_id', $seller->id)
            ->with(['product.category', 'product.language']);

        if ($request->filled('category_id')) {

            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $products = $query->get();


         $p = $products->map(function ($productSeller) {
            $product = $productSeller->product;

            return [
                'id' => $productSeller->id,
                'price' => $productSeller->price,
                'title' => $product->title,

            ];
        });

        return api_response($p);
    }
    public function storeDiscount(Request $request)
    {

        $id = $request->input('product');
        $seller = auth()->user()->seller;
        $query = ProductSeller::where('seller_id', $seller->id)->where('id', $id)->first();
        if ($query->price < $request->input('off_price')) {
            return api_response([], 'مبلغ تخفیفی نباید بیشتر از قیمت محصول باشد', 400);
        }
        if($request->off_type == 'hourly') {
            $query->update([
                'discounted_price' => $request->off_price,
                'discount_expire_at' => $request->expire_at ?? null,
                'off_type' => $request->off_type ?? null,
            ]);
            return api_response([], 'تخفیف اعمال شد');
        }
        else{
            $query->update([
                'discounted_price' => $request->off_price,
                'discount_expire_at' =>      null,
                'off_type' => $request->off_type ?? null,
            ]);
            return api_response([], 'تخفیف اعمال شد');
        }
    }
    public function make_discount(Request $request)
    {
        $request->validate([
            'code'         => 'required|string|unique:discount_code_shop',
            'expire_at'    => 'nullable|date',
            'limit'  => 'nullable|integer',
            'percentage'    => 'required|max:100',
            'category_id'  => 'nullable',
        ], [], [
            'code.required'        => 'کد تخفیف الزامی است.',
            'code.string'          => 'کد تخفیف باید یک رشته باشد.',
            'code.unique'          => 'این کد تخفیف قبلاً ثبت شده است.',
            'expire_at.date'       => 'تاریخ انقضا باید یک تاریخ معتبر باشد.',
            'limit.integer'  => 'تعداد استفاده باید عدد صحیح باشد.',
            'percentage.required'   => 'درصد تخفیف الزامی است.',
            'percentage.max'        => 'حداکثر درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.',
        ]);


        $seller = auth()->user();
        $a =  DiscountCodeShop::create([
            'seller_id' => $seller->id,
            'code' => $request->code,
            'expire_at' => $request->expire_at,
            'usage_limit' => $request->limit ,
            'parentage' => $request->percentage,

        ]);

        if ($request->category_id === 0) {
            $a->update([
                'all' => 1 ,
                'category_id' => null,
            ]);
        }
        else{
            $a->update([
                'all' => 0 ,
                'category_id' => $request->category_id,
            ]);
        }
        return api_response([],'کد تخفیف ثبت شد');
    }

    public function deleteDiscount(Request $request , $id)
    {
        $seller = auth()->user()->seller;

        $pro = ProductSeller::where('seller_id', $seller->id)->where('id', $id)->first();
        $pro->update([
            'discounted_price' => $pro->price,
            'discount_expire_at' => null,
            'off_type' => null,
        ]);
        return api_response([],'تخفیف حذف شد');

    }

    public function discountIndex(Request $request)
    {

        $seller = auth()->user();
        $dis = DiscountCodeShop::where('seller_id' , $seller->id);
        if ($request->filled('category_id') && $request->category_id != 0) {
            $dis->where('category_id', $request->category_id);
        }
        else{
            $dis->where('category_id', null);
        }
        $discount =$dis->paginate();

        $discount->getCollection()->transform(function ($item) {
            return [
                'id' => $item->id,
                'code' => $item->code,
                'percentage' => $item->parentage,
                'expire_at' => $item->expire_at,
                'limit' => $item->usage_limit,
                'title' => $item->category->title ?? 'همه',
                'category_id' => $item->category_id ?? 0,
            ];
        });
        return api_response($discount);
    }


    public function update_discount(Request $request, $id)
    {
        $request->validate([
            'code'         => 'required|string|unique:discount_code_shop,code,' . $id,
            'expire_at'    => 'nullable|date',
            'limit'  => 'nullable|integer',
            'percentage'   => 'required|max:100',
            'category_id'  => 'nullable',
        ], [], [
            'code.required'        => 'کد تخفیف الزامی است.',
            'code.string'          => 'کد تخفیف باید یک رشته باشد.',
            'code.unique'          => 'این کد تخفیف قبلاً ثبت شده است.',
            'expire_at.date'       => 'تاریخ انقضا باید یک تاریخ معتبر باشد.',
            'limit.integer'  => 'تعداد استفاده باید عدد صحیح باشد.',
            'percentage.required'  => 'درصد تخفیف الزامی است.',
            'percentage.max'       => 'حداکثر درصد تخفیف نمی‌تواند بیشتر از ۱۰۰ باشد.',
        ]);

        $seller = auth()->user();

        $discount = DiscountCodeShop::where('seller_id', $seller->id)->findOrFail($id);

        $discount->update([
            'code'         => $request->code,
            'expire_at'    => $request->expire_at,
            'usage_limit'  => $request->limit,
            'parentage'    => $request->percentage,
        ]);

        if ($request->category_id === 0 || $request->category_id === '0') {
            $discount->update([
                'all'         => 1,
                'category_id' => null,
            ]);
        } else {
            $discount->update([
                'all'         => 0,
                'category_id' => $request->category_id,
            ]);
        }

        return api_response([], 'کد تخفیف با موفقیت ویرایش شد');
    }

    public function deleteCode($id)
    {
        $seller = auth()->user();
        DiscountCodeShop::where('seller_id', $seller->id)->where('id', $id)->delete();
        return api_response([],'کد تخفیف حذف شد');
    }

}
