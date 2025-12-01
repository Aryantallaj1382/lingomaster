<?php

namespace App\Http\Controllers\Api\Professors;

use Illuminate\Routing\Controller;  // حتماً از این استفاده کن
use App\Models\Professors\ClassPrice;
use App\Models\Professors\DiscountClassPrice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class  ClassPriceController extends Controller
{

    public function store(Request $request)
    {
        $type = 'general';

        if ($request->filled('special_day_id')) {
            $type = 'special';
        }
        if ($request->filled('first_few_count')) {
            $type = 'first_few';
        }
        if ($request->filled('start_date')) {
            $type = 'hourly';
        }

        $validated = $request->validate([
            'language_id' => 'required|exists:languages,id',
            'type' => 'nullable|in:private,group,workshop,webinar,placement_test',
            'child_price' => 'nullable|numeric',
            'teen_price' => 'nullable|numeric',
            'adult_price' => 'nullable|numeric',
            'beginner_price' => 'nullable|numeric',
            'intermediate_price' => 'nullable|numeric',
            'advanced_price' => 'nullable|numeric',
            'online_price' => 'nullable|numeric',
            'in_person_price' => 'nullable|numeric',
            'base_price' => 'nullable|numeric',
            'discount_for_10' => 'nullable|integer',
            'discount_for_15' => 'nullable|integer',
            'discount_for_20' => 'nullable|integer',
            'discount_type' => 'nullable|in:general,special,first_few,hourly,session_based',
            'discount_percentage' => 'nullable|integer|min:0|max:100',
            'start_date' => 'nullable',
            'end_date' => 'nullable|after_or_equal:start_date',
            'first_few_count' => 'nullable|integer',
            'special_day_id' => 'nullable|exists:special_day,id',
            'session_count' => 'nullable|integer',
            'description' => 'nullable|string',
        ], [], [
            'language_id' => 'شناسه زبان',
            'type' => 'نوع کلاس',
            'child_price' => 'قیمت کودک',
            'teen_price' => 'قیمت نوجوان',
            'adult_price' => 'قیمت بزرگسال',
            'beginner_price' => 'قیمت مبتدی',
            'intermediate_price' => 'قیمت متوسط',
            'advanced_price' => 'قیمت پیشرفته',
            'online_price' => 'قیمت آنلاین',
            'in_person_price' => 'قیمت حضوری',
            'base_price' => 'قیمت پایه',
            'discount_for_10' => 'تخفیف برای 10 نفر',
            'discount_for_15' => 'تخفیف برای 15 نفر',
            'discount_for_20' => 'تخفیف برای 20 نفر',
            'discount_type' => 'نوع تخفیف',
            'discount_percentage' => 'درصد تخفیف',
            'start_date' => 'تاریخ شروع',
            'end_date' => 'تاریخ پایان',
            'first_few_count' => 'تعداد نفرات اول',
            'special_day_id' => 'شناسه روز ویژه',
            'session_count' => 'تعداد جلسات',
            'description' => 'توضیحات',
        ]);


        $professorId = auth()->user()->professor->id;



        $validated['start_date'] = isset($validated['start_date'])
            ? Carbon::parse($validated['start_date'])->format('Y-m-d H:i:s')
            : null;


        $validated['end_date'] = isset($validated['end_date'])
            ? Carbon::parse($validated['end_date'])->format('Y-m-d H:i:s')
            : null;



        DB::beginTransaction();
        try {
            // یافتن یا ایجاد کلاس جدید
            $classPrice = ClassPrice::updateOrCreate(
                [
                    'professor_id' => $professorId,
                    'language_id' => $validated['language_id'],
                    'class_type' => $validated['type'],
                ],
                [
                    'child_price' => $validated['child_price'] ?? null,
                    'teen_price' => $validated['teen_price'] ?? null,
                    'adult_price' => $validated['adult_price'] ?? null,
                    'beginner_price' => $validated['beginner_price'] ?? null,
                    'intermediate_price' => $validated['intermediate_price'] ?? null,
                    'advanced_price' => $validated['advanced_price'] ?? null,
                    'online_price' => $validated['online_price'] ?? null,
                    'in_person_price' => $validated['in_person_price'] ?? null,
                    'base_price' => $validated['base_price'] ?? null,
                ]
            );

            // یافتن یا ایجاد تخفیف برای کلاس
            $discount = DiscountClassPrice::updateOrCreate(
                [
                    'professor_id' => $professorId,
                    'class_price_id' => $classPrice->id,
                    'class_mode' => $validated['type'],
                ],
                [
                    'discount_for_10' => $validated['discount_for_10'] ?? null,
                    'discount_for_15' => $validated['discount_for_15'] ?? null,
                    'discount_for_20' => $validated['discount_for_20'] ?? null,
                    'discount_type' => $type,
                    'discount_percentage' => $validated['discount_percentage'] ?? null,
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                    'first_few_count' => $validated['first_few_count'] ?? null,
                    'special_day_id' => $validated['special_day_id'] ?? null,
                    'session_count' => $validated['session_count'] ?? null,
                    'description' => $validated['description'] ?? null,
                ]
            );

            DB::commit();

            return api_response([] ,'با موفقیت اطلاعات ویرایش شد');

        } catch (\Exception $e) {
            DB::rollBack();
            return api_response(['error' => $e->getMessage()], 500);
        }
    }


    public function show(Request $request, $classType = null)
    {
        $professorId = auth()->user()->professor->id;
        $languageId = $request->query('language_id'); // دریافت language_id از کوئری

        $query = ClassPrice::with('discount')->where('professor_id', $professorId);

        if ($classType && $classType !== '{}') {
            $query->where('class_type', $classType);
        }

        if ($languageId) {
            $query->where('language_id', $languageId);
        }

        $classPrice = $query->first(); // فقط یک مورد بگیر

        if (!$classPrice) {
            return api_response([
                'type' => $classType,
                'language_id' => $languageId,
            ]);
        }

        return api_response($this->formatClassPrice($classPrice));
    }

    private function formatClassPrice($classPrice)
    {
        $type = 'general';

        if (!empty($classPrice->discount->special_day_id)) {
            $type = 'special';
        }
        if (!empty($classPrice->discount->first_few_count)) {
            $type = 'first_few';
        }
        if (!empty($classPrice->discount->start_date)) {
            $type = 'hourly';
        }

        return [
            'id' => $classPrice->id,
            'base_price' => $classPrice->base_price,
            'language_id' => $classPrice->language_id,
            'type' => $classPrice->class_type,
            'child_price' => $classPrice->child_price,
            'teen_price' => $classPrice->teen_price,
            'adult_price' => $classPrice->adult_price,
            'beginner_price' => $classPrice->beginner_price,
            'intermediate_price' => $classPrice->intermediate_price,
            'advanced_price' => $classPrice->advanced_price,
            'online_price' => $classPrice->online_price,
            'in_person_price' => $classPrice->in_person_price,
            'created_at' => $classPrice->created_at,
            'class_mode' => optional($classPrice->discount)->class_mode,
            'discount_for_10' => optional($classPrice->discount)->discount_for_10,
            'discount_for_15' => optional($classPrice->discount)->discount_for_15,
            'discount_for_20' => optional($classPrice->discount)->discount_for_20,
            'discount_type' => optional($classPrice->discount)->discount_type,
            'discount_percentage' => optional($classPrice->discount)->discount_percentage,
            'start_date' => optional($classPrice->discount)->start_date,
            'end_date' => optional($classPrice->discount)->end_date,
            'first_few_count' => optional($classPrice->discount)->first_few_count,
            'special_day_id' => optional($classPrice->discount)->special_day_id,
            'session_count' => optional($classPrice->discount)->session_count,
            'description' => optional($classPrice->discount)->description,
            'off_type' => $type,
        ];
    }



}
