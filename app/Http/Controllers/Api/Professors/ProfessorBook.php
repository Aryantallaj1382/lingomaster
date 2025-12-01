<?php

namespace App\Http\Controllers\Api\Professors;

use Illuminate\Routing\Controller;  // حتماً از این استفاده کن
use App\Models\contacts\api;
use App\Models\Professors\BookLearning;
use App\Models\Professors\LearningGoal;
use App\Models\Professors\ProfessorLearningGoal;
use App\Models\shop\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfessorBook extends Controller
{

    public function index(Request $request)
    {
        $professorId = auth()->user()->professor->id;
        $professorProductIds = BookLearning::where('professor_id', $professorId)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->toArray();

        $selectedSubgoals = ProfessorLearningGoal::where('professor_id', $professorId)
            ->pluck('subgoal_id')
            ->toArray();

        $goals = LearningGoal::whereHas('subgoals', function ($query) use ($selectedSubgoals) {
            $query->whereIn('id', $selectedSubgoals);
        })
            ->with(['subgoals' => function ($query) use ($selectedSubgoals, $request) {
                $query->whereIn('id', $selectedSubgoals)
                    ->with(['products' => function ($query) use ($request) {
                        if ($request->has('language_id')) {
                            $query->where('language_id', $request->language_id);
                        }

                        if ($request->has('age_group')) {
                            $query->where('age_group_id', $request->age_group);
                        }

                        if ($request->has('language_level')) {
                            $query->where('language_levels_id', $request->language_level);
                        }
                    }]);
            }])->get();

        $response = $goals->map(function ($goal) use ($professorId, $request, $professorProductIds) {
            $subgoals = $goal->subgoals->map(function ($subgoal) use ($professorId, $request ,$professorProductIds) {

                // همه BookLearnings
                $bookLearnings = $subgoal->bookLearnings()
                    ->when($request->has('status'), function ($query) use ($request) {
                        $query->where('status', $request->status);
                    })
                    ->get();

                // محصولات جداگانه (مستقیم از products جدول)
                $productsFromSubgoal = $subgoal->products()
                    ->when($request->has('language_id'), function ($q) use ($request) {
                        $q->where('language_id', $request->language_id);
                    })
                    ->when($request->has('age_group'), function ($q) use ($request) {
                        $q->where('age_group_id', $request->age_group);
                    })
                    ->when($request->has('language_level'), function ($q) use ($request) {
                        $q->where('language_levels_id', $request->language_level);
                    })
                    ->get();

                // حالا میایم bookLearnings + products رو ترکیب می‌کنیم
                $combinedProducts = collect();

                // اول کتاب‌های bookLearning رو اضافه می‌کنیم
                foreach ($bookLearnings as $book) {
                    $product = $book->product_id ? Product::find($book->product_id) : null;

                    $combinedProducts->push([
                        'id' => $book->id,
                        'title' => $book->title ?? optional($product)->title,
                        'language_id' => optional($book->language)->title ?? optional($product?->language)->title,
                        'age_group' => optional($book->ageGroup)->title ?? optional($product?->age_group)->title,
                        'language_level' => optional($book->languageLevel)->title ?? optional($product?->language_level)->title,
                        'subject' => optional($book->subject)->title ?? optional($product?->subject)->title,
                        'book_link' => $book->book_link,
                        'images' => $product && $product->images->first()
                            ? api::api . $product->images->first()->path
                            : null,
                        'is_checked' => in_array(optional($product)->id, $professorProductIds),
                        'status' => $book->status,
                    ]);
                }

                // بعد محصولاتی که هیچ BookLearning ندارن
                $existingProductIds = $bookLearnings->pluck('product_id')->filter()->unique();

                foreach ($productsFromSubgoal as $product) {
                    if (!$existingProductIds->contains($product->id)) {
                        $combinedProducts->push([
                            'id' => $product->id,
                            'title' => $product->title,
                            'language_id' => optional($product->language)->title,
                            'age_group' => optional($product->age_group)->title,
                            'language_level' => optional($product->language_level)->title,
                            'subject' => optional($product->subject)->title,
                            'book_link' => null,
                            'images' => $product->images->first()
                                ? api::api . $product->images->first()->path
                                : null,
                            'is_checked' => in_array($product->id, $professorProductIds),
                            'status' => null,
                        ]);
                    }
                }

                $isCheckedSubgoal = $combinedProducts->contains('is_checked', true);

                return [
                    'id' => $subgoal->id,
                    'title' => $subgoal->title,
                    'is_checked' => $isCheckedSubgoal,
                    'products' => $combinedProducts->values(),
                ];
            });

            $isCheckedGoal = $subgoals->contains('is_checked', true);

            return [
                'id' => $goal->id,
                'title' => $goal->title,
                'is_checked' => $isCheckedGoal,
                'subgoals' => $subgoals,
            ];
        });

        return api_response($response->values());
    }


    public function indexWithoutProduct(Request $request)
    {
        $professorId = auth()->user()->professor->id;

        $selectedSubgoals = ProfessorLearningGoal::where('professor_id', $professorId)
            ->pluck('subgoal_id')
            ->toArray();

        $goals = LearningGoal::whereHas('subgoals', function ($query) use ($selectedSubgoals) {
            $query->whereIn('id', $selectedSubgoals);
        })
            ->with(['subgoals' => function ($query) use ($selectedSubgoals) {
                $query->whereIn('id', $selectedSubgoals);
            }])->get();

        $response = $goals->map(function ($goal) use ($professorId, $request) {
            $subgoals = $goal->subgoals->map(function ($subgoal) use ($professorId, $request) {
                $books = BookLearning::where('professor_id', $professorId)
                    ->where('subgoal_id', $subgoal->id);

                if ($request->has('status')) {
                    $books->where('status', $request->status);
                }

                $books = $books->get()->map(function ($book) {
                    return [
                        'id' => $book->id,
                        'title' => $book->title,
                        'language_id' => optional($book->language)->title,
                        'age_group' => optional($book->age_group)->title,
                        'language_level' => optional($book->language_level)->title,
                        'subject' => optional($book->subject)->title,
                        'book_link' => $book->book_link,
                        'images' => null, // چون این داده‌ها از `BookLearning` هستند، تصویر نداریم
                        'is_checked' => true, // چون مستقیماً از `BookLearning` دریافت می‌شود، همیشه `true` است
                        'status' => $book->status,
                    ];
                });

                $isCheckedSubgoal = $books->isNotEmpty();

                return [
                    'id' => $subgoal->id,
                    'title' => $subgoal->title,
                    'is_checked' => $isCheckedSubgoal,
                    'products' => $books, // در اینجا `products` همان `BookLearning` است
                ];
            })->values();

            $isCheckedGoal = $subgoals->contains('is_checked', true);

            return [
                'id' => $goal->id,
                'title' => $goal->title,
                'is_checked' => $isCheckedGoal,
                'subgoals' => $subgoals,
            ];
        })->values();

        return api_response($response);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'nullable|array',
            'product_id.*' => 'exists:products,id',
            'name' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255',
            'language' => 'nullable',
            'age_group' => 'nullable',
            'subject' => 'nullable',
            'type' => 'nullable',
            'learning_goals' => 'nullable',
            'language_level' => 'nullable',
        ], [], [
            'product_id' => 'شناسه محصول',
            'product_id.*' => 'شناسه محصول',
            'name' => 'نام',
            'link' => 'لینک',
            'language' => 'زبان',
            'age_group' => 'گروه سنی',
            'subject' => 'موضوع',
            'type' => 'نوع',
            'learning_goals' => 'اهداف یادگیری',
            'language_level' => 'سطح زبان',
        ]);


        $professorId = auth()->user()->professor->id;
        $books = [];

        if ($request->has('product_id') && is_array($request->product_id)) {
            $products = Product::whereIn('id', $request->product_id)->get();

            foreach ($products as $product) {
                $books[] = BookLearning::create([
                    'professor_id' => $professorId,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'book_link' => null,
                    'language_id' => $product->language_id,
                    'age_group_id' => $product->age_group_id,
                    'subgoal_id' => $product->subject_id,
                    'language_level_id' => $product->language_level_id,
                    'status' => 'approved',
                ]);
            }
        }

        if ($request->filled('name')) {
            $books[] = BookLearning::create([
                'professor_id' => $professorId,
                'title' => $request->name,
                'type' => $request->type,
                'subject' => $request->subject,
                'book_link' => $request->link,
                'language_id' => $request->language,
                'age_group_id' => $request->age_group,
                'subgoal_id' => $request->learning_goals,
                'language_level_id' => $request->language_level,
                'status' => 'pending',
            ]);
        }

        return api_response($books, 'کتاب‌ها با موفقیت ثبت شدند.');
    }

    public function update(Request $request, BookLearning $book)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:255',
            'language' => 'nullable|exists:languages,id',
            'age_group' => 'nullable|exists:age_groups,id',
            'subject' => 'nullable|exists:subjects,id',
            'type' => 'nullable|string',
            'learning_goals' => 'nullable|exists:learning_goals,id',
            'language_level' => 'nullable|exists:language_levels,id',
        ]);

        $professorId = auth()->user()->professor->id;

        if ($book->professor_id !== $professorId) {
            return api_response(null, 'شما اجازه ویرایش این کتاب را ندارید.', false, 403);
        }

        $book->update([
            'title' => $request->name ?? $book->title,
            'type' => $request->type ?? $book->type,
            'subject' => $request->subject ?? $book->subject,
            'book_link' => $request->link ?? $book->book_link,
            'language_id' => $request->language ?? $book->language_id,
            'age_group_id' => $request->age_group ?? $book->age_group_id,
            'subgoal_id' => $request->learning_goals ?? $book->subgoal_id,
            'language_level_id' => $request->language_level ?? $book->language_level_id,
        ]);

        return api_response($book, 'کتاب با موفقیت بروزرسانی شد.');
    }


    public function destroy(BookLearning $book)
    {
        $professorId = auth()->user()->professor->id;

        if ($book->professor_id !== $professorId) {
            return api_response(null, 'شما اجازه حذف این کتاب را ندارید.', 403);
        }

        $book->delete();

        return api_response(null, 'کتاب با موفقیت حذف شد.');
    }



}
