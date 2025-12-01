<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\AgeGroupRequest;
use App\Models\AgeGroup;

class AgeGroupsController extends Controller
{
    public function index()
 {
     $age_group = AgeGroup::latest()->paginate();

     return api_response($age_group);
 }

    public function store(AgeGroupRequest $request)
 {
     $age_group = AgeGroup::create($request->validated());

     return api_response($age_group , 'رده سنی با موفقیت افزوده شد');

 }
    public function update(AgeGroupRequest $request, AgeGroup $ageGroup)
    {
        $ageGroup->update($request->validated());

        return api_response($ageGroup, 'اطلاعات رده سنی با موفقیت ویرایش شد');
    }


    public function destroy(AgeGroup $ageGroup)
    {
        $ageGroup->delete();

        return api_response([], 'اطلاعات رده سنی با موفقیت حذف شد');
    }
}
