<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Professors\BookLearning;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningSubgoal;
use App\Models\shop\Category;
use App\Models\shop\Product;
use App\Models\shop\Publication;
use App\Models\shop\Subjects;
use Illuminate\Http\Request;

class LibreryController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('just_show' ,true)->exceptHelpProducts()->with(['images', 'category'])
            ->filterByRequest($request)
            ->sortByRequest($request);

        $products = $query->paginate(15);

        $products->getCollection()->transform(function ($product) {
            $prof = BookLearning::where('product_id' , $product->id)->where('status' , 'approved')->get();

            return [
                'id' => $product->id,
                'view' => $product->view,
                'title' => $product->title,
                'language' => $product->language->title ?? null,
                'flag' => $product->language->icon ?? null,
                'subject' => $product->goal?->title,
                'dimensions' => $product->Dimensions,
                'image' => $product->first_image,
                'selected_seller' => $product->selected_seller,
                'category' => $product->category->title ?? 'نامشخص',
                'is_like' => $product->is_liked,
                'price' => $product->default_price,
                'is_download' => $product->is_download,
                'professors' => $prof->map(function($prof){
                    return api::api.$prof->professors->profile;
                }),



            ];
        });

        return api_response($products);
    }
    public function show($id)
    {
        $product = Product::find($id);

        $transformProductItem = function ($product) {
            return [
                'id' => $product->id,
                'title' => $product->title,
                'image' => $product->images->isNotEmpty() ?api::api  . $product->images->first()->path : null,
                'selected_seller' => $product->selected_seller,
                'category' => $product->category->title ?? 'نامشخص',
                'is_like' => $product->is_liked,
                'is_download' => $product->is_download,
            ];
        };
        $similarByCategory = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['images', 'category'])
            ->latest()
            ->take(4)
            ->get()
            ->map($transformProductItem);

        $sampleImages = $product->SampleImages->map(function ($sampleImage) {
            return [
                'path' => api::api  . $sampleImage->path,
                'id' => $sampleImage->id,
            ];
        })->toArray();
        $videoPath = $product->videos->isNotEmpty() ? api::api  . $product->videos->first()->path : null;
        $covers = $product->covers->isNotEmpty() ? api::api  . $product->covers->first()->path : null;

        $data =[
            'id' => $product->id,
            'view' => $product->view,
            'level' => $product->language_level->title,
            'title' => $product->title,
            'language' => $product->language->title ?? null,
            'flag' => $product->language->icon ?? null,
            'tags' => $product->tags ?? null,
            'subject' => $product->goal?->title,
            'dimensions' => $product->Dimensions,
            'image' => $product->first_image,
            'selected_seller' => $product->selected_seller,
            'category' => $product->category->title ?? 'نامشخص',
            'is_like' => $product->is_liked,
            'is_download' => $product->is_download,
            'rate_count' => $product->ratings_count,
            'rate' => $product->user_rating,
            'author' => $product->author,
            'Volume_number' => $product->Volume_number,
            'page_number' => $product->page_number,
            'age_group' => $product->age_group->title ?? null,
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
            'category_slug' => $product->category->slug ?? null,
            'video' => $videoPath,
            'video_covers' => $covers,
            'book_category' => $product->book_category,
            'audio_file' => $product->audio_file,
            'video_file' => $product->video_file,
            'video_accent' => $product->video_accent,
            'audio_accent' => $product->audio_accent,
            'sample_images' => $sampleImages,
            'average_rate' => $product->average_rating,
            'just_show' => $product->just_show,
            'similar' => $similarByCategory,
            'professors' => BookLearning::where('product_id', $product->id)
                ->get()
                ->map(function ($bookLearning) {
                    return [
                        'profile' => $bookLearning->professors->profile,
                        'name' => $bookLearning->professors->full_name(),
                        'id' => $bookLearning->professors->id,
                    ];
                }),
        ];
        return api_response($data);
    }


    public function GetFilter()
    {
        $category1 = Category::where('id', '!=', 1)->get();

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
        $subjectIds = $products->pluck('learning_subgoal_id')->unique();
        $languageIds = $products->pluck('language_id')->unique();
        $level = $products->pluck('language_levels_id')->unique();

        $publications = Publication::whereIn('id', $publicationIds)->get();
        $ageGroups = AgeGroup::whereIn('id', $ageGroupIds)->get();
        $subjects = LearningSubgoal::whereIn('id', $subjectIds)->get();
        $languages = Languages::whereIn('id', $languageIds)->get();
        $level = LanguageLevel::whereIn('id', $level)->get();

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
            'learning_goals' => LearningSubgoal::with(['goal'])
                ->get()
                ->groupBy(fn($learningGoal) => $learningGoal->goal->title ?? null)
                ->map(function ($learningGoalsGroup) {
                    $firstGoal = $learningGoalsGroup->first()->goal;

                    return [
                        'goal_id' => $firstGoal->id,
                        'goal_title' => $firstGoal->title,
                        'subgoals' => $learningGoalsGroup->map(fn($learningGoal) => [
                            'value' => $learningGoal->id,
                            'name' => $learningGoal->title,
                        ])->values(),
                    ];
                })
                ->values()
                ->toArray(),

            'level' => $level->map(fn($level) => [
                'value' => $level->id,
                'name' => $level->title,
            ])->toArray(),
            'language' => $languages->map(fn($language) => [
                'value' => $language->id,
                'name' => $language->title,
            ])->toArray(),
            'price_range' => [
                'min_price' => (int)$minPrice ?? 0,
                'max_price' => (int)$maxPrice ?? 0,
            ],
            'Dimensions' => [
                [  'value' => 1,
                    'name' => 'تک جلدی',
                    ],
                [  'value' => 2,
                    'name' => 'چند جلدی',
                ],
            ],
            'time_of_print' => [
                [  'value' => 1,
                    'name' => 'اول',
                ],
                [  'value' => 2,
                    'name' => 'دوم',
                ],
                [  'value' => 3,
                    'name' => 'سوم',
                ],
                [  'value' => 4,
                    'name' => 'چهارم',
                ],
                [  'value' => 5,
                    'name' => 'پنجم',
                ],
                [  'value' => 6,
                    'name' => 'ششم',
                ],
            ]
        ];

        return api_response($result, 'فیلتر با موفقیت ارسال شد');
    }

}
