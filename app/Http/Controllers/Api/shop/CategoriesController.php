<?php

namespace App\Http\Controllers\Api\shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\shop\CategoriesRequest;
use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningGoal;
use App\Models\shop\Category;
use App\Models\shop\Publication;
use Illuminate\Support\Str;

class CategoriesController extends Controller
{
    public function index()
    {
        $categories = Category::get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'title' => $category->title,
                    'slug' => $category->slug,
                    'is_physical' => $category->is_physical,
                    'icon' => $category ? 'https://api.lingomasters.ir/storage/' . $category->icon: null,
                ];
            });
        return api_response($categories);
    }

}
