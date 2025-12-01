<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\LangugesRequest;
use App\Models\Languages;

class LangugesController extends Controller
{
    public function index()
    {
        $languages = Languages::latest();

        return api_response($languages);
    }


}
