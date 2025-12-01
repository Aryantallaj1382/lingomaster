<?php

namespace App\Http\Controllers;

use App\Models\Questionnaire;
use Illuminate\Http\Request;

class QuestionnaireController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            // اعتبارسنجی مثل قبل
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:255',
            'degree' => 'nullable|string|max:50',
            'university_name' => 'nullable|string|max:255',
            'graduation_date' => 'nullable|string|max:50',
            'field_of_study' => 'nullable|string|max:255',
            'english_teaching' => 'nullable|boolean',
            'english_literature' => 'nullable|boolean',
            'english_translation' => 'nullable|boolean',
            'other_fields' => 'nullable|string|max:255',
            'online_teaching' => 'nullable|array',
            'online_challenges' => 'nullable|string',
            'offline_experience' => 'nullable|string',
            'is_cooperating_now' => 'nullable|boolean',
            'cooperation_platform' => 'nullable|string|max:255',
            'cooperation_details' => 'nullable|string',
            'expected_salary' => 'nullable|string|max:50',
            'is_fulltime' => 'nullable|boolean',
            'teaching_hours' => 'nullable|array',
            'age_groups' => 'nullable|array',
            'teaching_levels' => 'nullable|array',
            'class_types' => 'nullable|array',
            'books_taught' => 'nullable|string',
            'ttc_experience' => 'nullable|string',
            'is_willing_ttc' => 'nullable|boolean',
            'ttc_type' => 'nullable|array',
            'available_from' => 'nullable|string|max:50',
            'short_term_goals' => 'nullable|string',
            'mid_term_goals' => 'nullable|string',
            'long_term_goals' => 'nullable|string',
            'weaknesses' => 'nullable|string',
            'strengths' => 'nullable|string',
            'can_teach_general' => 'nullable|boolean',
            'can_teach_specialized' => 'nullable|boolean',
            'wants_improvement' => 'nullable|boolean',
        ]);

        // ذخیره پرسشنامه
        $questionnaire = Questionnaire::create($validated);

        // تولید کد یونیک
        do {
            $code = $this->generateCode(6);
            $exists = Questionnaire::where('code', $code)->exists();
        } while($exists);

        // ذخیره کد در جدول
        $questionnaire->update(['code' => $code]);

        return response()->json(['code' => $code
        ,'message' => 'با موفقیت ذخیره شد'
        ]);
    }

// تابع کمکی تولید کد
    private function generateCode($length = 6)
    {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $code = '';
        $maxIndex = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, $maxIndex)];
        }

        return $code;
    }

}
