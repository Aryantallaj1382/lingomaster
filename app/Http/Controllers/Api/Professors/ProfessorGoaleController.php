<?php

namespace App\Http\Controllers\Api\Professors;

use App\Models\contacts\api;
use Illuminate\Routing\Controller;  // حتماً از این استفاده کن
use App\Models\Professors\LearningGoal;
use App\Models\Professors\LearningSubgoal;
use App\Models\Professors\ProfessorLearningGoal;


use Illuminate\Http\Request;

class ProfessorGoaleController extends Controller
{


    public function showGroupedGoals()
    {
        $professor = auth()->user()->professor;

        // زبان‌هایی که استاد انتخاب کرده (از جدول واسط)
        $selectedLanguageIds = $professor->languages()->pluck('languages.id')->toArray();

        // دریافت اهداف استاد و روابط subgoal و language
        $professorGoals = ProfessorLearningGoal::where('professor_id', $professor->id)
            ->with(['subgoal.goal', 'language'])
            ->get();

        $groupedGoals = [];

        foreach ($professorGoals as $subgoal) {
            $language = $subgoal->language;

            // اگر زبان در لیست زبان‌های انتخاب شده استاد نیست، رد کن
            if (!$language || !in_array($language->id, $selectedLanguageIds)) {
                continue;
            }

            $goalTitle = $subgoal->subgoal->goal->title;
            $subValue = $subgoal->subgoal->sub ?? 'without_sub';

            if (!isset($groupedGoals[$language->title])) {
                $groupedGoals[$language->title] = [
                    'language_title' => $language->title,
                    'language_flag' => api::api.$language->flag,
                    'goals' => []
                ];
            }

            if (!isset($groupedGoals[$language->title]['goals'][$goalTitle])) {
                $groupedGoals[$language->title]['goals'][$goalTitle] = [];
            }

            if (!isset($groupedGoals[$language->title]['goals'][$goalTitle][$subValue])) {
                $groupedGoals[$language->title]['goals'][$goalTitle][$subValue] = [
                    'subgoals' => [],
                ];
            }

            $groupedGoals[$language->title]['goals'][$goalTitle][$subValue]['subgoals'][] = [
                "title" => $subgoal->subgoal->title,
                "id" => $subgoal->subgoal->id,
            ];
        }

        return api_response(array_values($groupedGoals), 'ok');
    }


    public function show()
    {
        $professorId = auth()->user()->professor->id;

        $professorSelectedGoals = ProfessorLearningGoal::where('professor_id', $professorId)
            ->pluck('subgoal_id')
            ->toArray();

        $allSubgoals = LearningSubgoal::with('goal')->get()->groupBy('goal.id');

        $data = [
            "goals" => [],
            "exams" => [
                [
                    "id" => "1",
                    "title" => "داخل ایران",
                    "content" => []
                ],
                [
                    "id" => "2",
                    "title" => "بین المللی",
                    "content" => []
                ]
            ]
        ];

        $goalCategories = [
            1 => "فنون زبان",
            2 => "مهارت ها",
            3 => "زبان مدرسه",
            4 => "زبان دانشگاه",
            5 => "زبان کنکور",
            6 => "ترجمه",
            7 => "آزمون ها",
            8 => "مشاغل",
            9 => "مصاحبه",
            10 => "لهجه و تلفظ",
            11 => "تربیت مدرس TTC",
        ];

        foreach ($goalCategories as $goalId => $goalTitle) {
            $goalData = [
                "id" =>  $goalId,
                "title" => $goalTitle,
                "is_check" => false, // مقدار اولیه
                "content" => ($goalId === 7) ? " " : [],
            ];


            if ($allSubgoals->has($goalId) && $goalId !== 7) {
                $subgoals = $allSubgoals[$goalId]->groupBy('sub');

                foreach ($subgoals as $subTitle => $groupedSubgoals) {
                    $subgroupData = [
                        "title" => $subTitle ?: "بدون دسته‌بندی",
                        "data" => [],
                    ];

                    foreach ($groupedSubgoals as $subgoal) {
                        $isChecked = in_array($subgoal->id, $professorSelectedGoals);
                        $subgroupData["data"][] = [
                            "title" => $subgoal->title,
                            "id" => $subgoal->id,
                            "is_check" => $isChecked,
                        ];

                        if ($isChecked) {
                            $goalData["is_check"] = true;
                        }
                    }

                    $goalData["content"][] = $subgroupData;
                }
            }

            if ($goalId === 7) {
                $examSubgoals = $allSubgoals[$goalId] ?? collect();
                $examGroups = $examSubgoals->groupBy('sub');

                foreach ($examGroups as $subTitle => $groupedSubgoals) {
                    $subgroupData = [
                        "title" => $subTitle ?: "بدون دسته‌بندی",
                        "data" => [],
                    ];

                    foreach ($groupedSubgoals as $subgoal) {
                        $subgroupData["data"][] = [
                            "title" => $subgoal->title,
                            "id" => $subgoal->id,
                        ];
                    }

                    if (strpos($subTitle, 'داخل ایران') !== false) {
                        $data["exams"][0]["content"][] = $subgroupData;
                    } else {
                        $data["exams"][1]["content"][] = $subgroupData;
                    }
                }
            }

            $data["goals"][] = $goalData;
        }

        return api_response($data);
    }

    public function storeProfessorLearningGoals(Request $request)
    {
        $validated = $request->validate([
            'language_id' => 'required|exists:languages,id',
            'subgoals' => 'required|array',
            'subgoals.*' => 'exists:learning_subgoals,id',
        ], [], [
            'language_id' => 'شناسه زبان',
            'subgoals' => 'زیرهدف‌ها',
            'subgoals.*' => 'زیرهدف',
        ]);


        $professorId = auth()->user()->professor->id;

        ProfessorLearningGoal::where('professor_id', $professorId)
            ->where('language_id', $validated['language_id'])
            ->delete();

        foreach ($validated['subgoals'] as $subgoalId) {
            ProfessorLearningGoal::create([
                'professor_id' => $professorId,
                'language_id' => $validated['language_id'],
                'subgoal_id' => $subgoalId,
            ]);
        }

        return api_response([], 'اهداف یادگیری با موفقیت ذخیره شدند.');
    }
}
