<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\PublicationRequest;
use App\Models\shop\Publication;

class ToolsController extends Controller
{
    public function index()
    {
        $Publications = Publication::latest()->paginate();

        return api_response($Publications);
    }



    public function store(PublicationRequest $request)
    {

        $Publications = Publication::create($request->validated());

        return api_response($Publications , 'انتشارات با موفقیت افزوده شد');
    }



    public function update(PublicationRequest $request, Publication $Publications)
    {
        $Publications->update($request->validated());

        return api_response($Publications, 'اطلاعات انتشارات با موفقیت ویرایش شد');
    }



    public function destroy(Publication $Publications)
    {
        $Publications->delete();

        return api_response([], 'اطلاعات انتشارات با موفقیت حذف شد');
    }}
