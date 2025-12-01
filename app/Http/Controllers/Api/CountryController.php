<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\shop\IranCity;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function getCountries(Request $request)
    {
        $country_id = $request->query('country');

        if ($country_id) {
            $city = IranCity::where('countries_id', $country_id)->select('id', 'name')->get();
        } else {
            $city = collect([]);
        }
        $countries = Country::select('id', 'title')->get();
        return api_response([
            'countries'=>$countries->map(function ($country) {
                return [
                    'value' => $country->id,
                    'name' => $country->title,
                ];
            }),
            'city'=>$city->map(function ($city) {
                return [
                    'value' => $city->id,
                    'name' => $city->name,
                ];
            }) ,
            ]);
    }
}
