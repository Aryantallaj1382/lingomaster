<?php

namespace App\Http\Controllers\Api\Professors;

use App\Models\PlacementTest;
use App\Models\ProfessorClass;
use App\Models\TrialClass;
use Illuminate\Routing\Controller;

// حتماً از این استفاده کن
use App\Models\Professors\ClassType;
use App\Models\Professors\ClassTypeOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClassTypeController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'language_id' => 'required|exists:languages,id',
            'class_type' => 'array|nullable',
            'class_type.*' => 'string',
            'testClass' => 'nullable',
            'levelAssessmentClass' => 'nullable',
            'placement_tests' => 'array|nullable',
            'placement_tests.*.age' => 'required|exists:age_groups,id',
            'placement_tests.*.pricing' => 'required|string',
            'placement_tests.*.skill' => 'array|required',
            'placement_tests.*.skill.*.value' => 'required|exists:skills,id',
        ], [], [
            'language_id' => 'شناسه زبان',
            'testClass' => 'کلاس تست',
            'class_type' => 'نوع کلاس',
            'class_type.*' => 'نوع کلاس',
            'placement_tests' => 'آزمون‌های جایابی',
            'placement_tests.*.age' => 'گروه سنی',
            'placement_tests.*.pricing' => 'نوع قیمت گذاری',
            'placement_tests.*.skill' => 'مهارت‌ها',
            'placement_tests.*.skill.*.value' => 'مقدار مهارت',
        ]);

        $trialTypeMapping = [
            '1' => 'free',
            '3' => 'no_discount',
            '2' => '50_percent',

        ];

        $professor = auth()->user()->professor->id;

        $processedData = [
            'language_id' => $data['language_id'],
            'professor_id' => $professor,
            'webinar' => in_array('webinar', $data['class_type']),
            'workshop' => in_array('workshop', $data['class_type']),
            'private_class' => in_array('private', $data['class_type']),
            'group_class' => in_array('group', $data['class_type']),
        ];

        $record = ProfessorClass::where('language_id', $data['language_id'])
            ->where('professor_id', $professor)
            ->first();

        if ($record) {
            $record->update($processedData);
        } else {
            ProfessorClass::create($processedData);
        }

        $isTrial = false;
        $discount = null;

        if ($request->has('testClass') && array_key_exists($data['testClass'], $trialTypeMapping)) {
            $isTrial = true;
            $discount = $trialTypeMapping[$data['testClass']];
        }

        $trialClassRecord = TrialClass::where('language_id', $data['language_id'])
            ->where('professor_id', $professor)
            ->first();

        if ($trialClassRecord) {
            $trialClassRecord->update([
                'is_trial' => $isTrial,
                'discount' => $discount,
            ]);
        } else {
            TrialClass::create([
                'language_id' => $data['language_id'],
                'professor_id' => $professor,
                'is_trial' => $isTrial,
                'discount' => $discount,
            ]);
        }

        // ✅ ثبت کلاس‌های تعیین سطح:
        PlacementTest::where('professor_id', $professor)
            ->where('language_id', $data['language_id'])
            ->delete(); // پاک‌سازی قبلی‌ها برای بروزرسانی کامل
        if ($request->levelAssessmentClass == true) {
            foreach ($request->placement_tests as $test) {
                $priceType = match ($test['pricing']) {
                    '1' => 'free',
                    '3' => 'no_discount',
                    '2' => '50_percent',
                    default => 'no_discount',
                };

                $placementTest = PlacementTest::create([
                    'professor_id' => $professor,
                    'language_id' => $data['language_id'],
                    'age_group_id' => $test['age'],
                    'price' => $priceType,
                ]);

                $skillIds = collect($test['skill'])->pluck('value')->toArray();
                $placementTest->skills()->attach($skillIds);
            }

        }

        return api_response([], 'اطلاعات با موفقیت ثبت شد');
    }

    public function show(Request $request)
    {
        $request->validate([
            'language_id' => 'nullable|exists:languages,id',
        ]);

        $professorId = auth()->user()->professor->id;

        // بازیابی رکورد مربوط به استاد و زبان مشخص
        $record = ProfessorClass::where('language_id', $request->language_id)
            ->where('professor_id', $professorId)
            ->first();

        // اطلاعات کلاس آزمایشی
        $trialRecord = TrialClass::where('language_id', $request->language_id)
            ->where('professor_id', $professorId)
            ->first();

        // اگر رکورد کلاس عمومی یافت نشد، پاسخ خالی بازگردانید
        $classType = [];
        if ($record) {
            // ساخت آرایه class_type بر اساس فیلدهای بولین
            if ($record->webinar) {
                $classType[] = 'webinar';
            }
            if ($record->workshop) {
                $classType[] = 'workshop';
            }
            if ($record->private_class) {
                $classType[] = 'private';
            }
            if ($record->group_class) {
                $classType[] = 'group';
            }
        }

        $isTrial = false;
        $discount = null;
        $discountTitle = null;
        if ($trialRecord) {
            $isTrial = $trialRecord->is_trial;
            $discount = $trialRecord->discount;
            $discountTitle = $trialRecord->discount_title;
        }
        $levelAssessmentClass = false;
        $placementTests = [];

        $placementRecords = PlacementTest::with(['skills'])
            ->where('language_id', $request->language_id)
            ->where('professor_id', $professorId)
            ->get();

        if ($placementRecords->isNotEmpty()) {
            $levelAssessmentClass = true;

            $placementTests = $placementRecords->map(function ($placement) {
                return [
                    'age' => (string)$placement->ageGroup->id,
                    'label' => (string)$placement->ageGroup->title,
                    'pricing' => (string)$placement->price_fa,
                    'skill' => $placement->skills->map(function ($skill) {
                        return [
                            'value' => (string)$skill->id,
                            'label' => $skill->name,
                        ];
                    })->values()
                ];
            })->values();
        }

        // پاسخ
        return api_response([
            'class_type' => $classType,
            'levelAssessmentClass' => $levelAssessmentClass,
            'language_id' => $request->language_id,
            'testClass' => $discount,
            'placement_tests' => $placementTests
        ], 'ok');
    }

}
