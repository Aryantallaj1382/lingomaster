<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\ProductTypeRequest;
use App\Models\shop\ProductType;

class productTypeController extends Controller
{
    public function index()
    {
        $product_type = ProductType::latest()->paginate();

        return api_response($product_type);
    }

    public function store(ProductTypeRequest $request)
    {
        $product_type = ProductType::create($request->validated());

        return api_response($product_type , 'نوع محصول با موفقیت یاشخته شد');
    }


    public function update(ProductTypeRequest $request, ProductType $productType)
    {
        $productType->update($request->validated());

        return api_response($productType, 'اطلاعات نوع محصول با موفقیت ویرایش شد');
    }

    public function destroy(ProductType $productType)
    {
        $productType->delete();

        return api_response([], 'اطلاعات نوع محصول با موفقیت حذف شد');
    }
}
