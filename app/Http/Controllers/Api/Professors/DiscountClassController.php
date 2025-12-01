<?php

namespace App\Http\Controllers\Api\Professors;

use Illuminate\Routing\Controller;  // حتماً از این استفاده کن
use App\Models\Professors\DiscountClass;
use Illuminate\Http\Request;

class DiscountClassController extends Controller
{

    public function store(Request $request)
    {
        $request->validate([
            'class_mode' => 'required|in:private,group,workshop,webinar,assessment',
            'reservation_id' => 'required|exists:reservations,id',
            'discount_type' => 'required|in:general,special,first_few,hourly',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'first_few_count' => 'nullable|integer',
            'special_day_id' => 'nullable|exists:special_day,id',
            'description' => 'nullable|string',
        ], [], [
            'class_mode' => 'مدل کلاس',
            'reservation_id' => 'شناسه رزرو',
            'discount_type' => 'نوع تخفیف',
            'discount_percentage' => 'درصد تخفیف',
            'start_date' => 'تاریخ شروع',
            'end_date' => 'تاریخ پایان',
            'first_few_count' => 'تعداد نفرات اول',
            'special_day_id' => 'شناسه روز ویژه',
            'description' => 'توضیحات',
        ]);

        $professorId = auth()->user()->professor->id;

        $discount = DiscountClass::create([
            'professor_id' => $professorId,
            'class_mode' => $request->class_mode,
            'reservation_id' => $request->reservation_id,
            'discount_type' => $request->discount_type,
            'discount_percentage' => $request->discount_percentage,
            'start_date' => $request->start_date??null,
            'end_date' => $request->end_date??null,
            'first_few_count' => $request->first_few_count??null,
            'special_day_id' => $request->special_day_id??null,
            'description' => $request->description ??null,
        ]);

        return api_response($discount);
    }
}
