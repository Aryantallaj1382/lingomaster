<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Models\iran_regions\IranCity;
use App\Models\iran_regions\IranProvince;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index_province(Request $request)
    {
        $provinces = IranProvince::orderBy('name');

        if ($request->filled('search'))
            $provinces->where('name', 'like', '%' . $request->search . '%');

        $provinces = $provinces->get(['id', 'name']);

        foreach ($provinces as &$province) {
            $province['value'] = $province->id;
        }

        return api_response($provinces);
    }

    public function index_city(Request $request)
    {
        $cities = IranCity::orderBy('name');

        if ($request->filled('province'))
            $cities->where(function (Builder $query) use ($request) {
                return $query->where('province_id', '=', $request->province)
                    ->orWhereRelation('province', 'name', 'like', '%' . $request->province . '%');
            });

        if ($request->filled('search'))
            $cities->where('name', 'like', '%' . $request->search . '%');

        $cities = $cities->get(['id', 'name']);

        foreach ($cities as &$city) {
            $city['value'] = $city->id;
        }

        return api_response($cities);
    }

}
