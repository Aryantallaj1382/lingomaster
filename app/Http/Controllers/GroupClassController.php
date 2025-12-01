<?php

namespace App\Http\Controllers;

use App\Models\DiscountForGroupClass;
use App\Models\GroupClass;
use App\Models\GroupClassBook;
use App\Models\Professors\Online;
use App\Models\Professors\ProfessorLearningGoal;
use Illuminate\Http\Request;

class GroupClassController extends Controller
{
    public function show()
    {
        $professor = auth()->user()->professor;
        $data = [
            'learning_goals' => $professor->learningGoals()->with(['language', 'subgoal'])->get()->map(function ($learning_goals) {
                return [
                    'id' => $learning_goals->id,
                    'title' => $learning_goals->subgoal->title,
                ];
            }),
            'language_levels' => $professor->languageLevels()->get()->map(function ($level) {
                return [
                    'id' => $level->id,
                    'title' => $level->title,
                ];
            }),
            'gender' => $professor->genders()->get()->map(function ($gender) {
                return [
                    'id' => $gender->id,
                    'title' => $gender->title,
                ];
            }),
            'ageGroups' => $professor->ageGroups()->get()->map(function ($ageGroups) {
                return [
                    'id' => $ageGroups->id,
                    'title' => $ageGroups->title,
                ];
            }),
            'platforms' => Online::where('professor_id', $professor->id)->get()->map(function ($online) {
                return [
                    'platform_name' => $online->platform_name,
                ];
            }),
        ];

        return response()->json($data);
    }


    public function getPendingGroupClass(Request $request)
    {
        $professorId = auth()->user()->professor->id;

        $groupClass = GroupClass::where('professors_id', $professorId)
            ->where('professor_status', 'pending')
            ->with('books','schedules','discount')
            ->first();

        if ($groupClass) {
            return api_response($groupClass, 'کلاس پندینگ پیدا شد');
        }

        return api_response(null, 'کلاس پندینگ وجود ندارد');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'informations.teaching_type_id' => 'required|exists:teaching_types,id',
            'informations.gender_id' => 'required|exists:genders,id',
            'informations.age_group_id' => 'required|exists:age_groups,id',
            'informations.level_id' => 'required|exists:language_levels,id',
            'informations.subject_id' => 'required',
            'informations.language_id' => 'required|exists:languages,id',
            'informations.min_capacity' => 'required|integer|min:1',
            'informations.max_capacity' => 'required|integer|min:1',
            'informations.sessions_count' => 'required|integer|min:1',
            'informations.platform' => 'required|string',
            'informations.days' => 'nullable',
            'informations.hourly' => 'nullable',

            'books' => 'required|array',
            'books.*.product_id' => 'nullable|exists:products,id',
            'books.*.name' => 'nullable|string',
            'books.*.book_type' => 'nullable|string',
            'books.*.book_link' => 'nullable|string',
            'books.*.type' => 'nullable|string',
        ]);
         $professor = auth()->user()->professor->id;
        $data['informations']['professors_id'] = $professor;
        $data['informations']['professor_status'] = 'pending';

        // بررسی وجود کلاس گروهی پندینگ برای استاد
        $groupClass = GroupClass::where('professors_id', $professor)
            ->where('professor_status', 'pending')
            ->first();

        if ($groupClass) {
            $groupClass->update($data['informations']);
        } else {
            $groupClass = GroupClass::create($data['informations']);
        }

        GroupClassBook::where('group_class_id', $groupClass->id)->delete();

        foreach ($data['books'] as $book) {
            GroupClassBook::create(array_merge($book, ['group_class_id' => $groupClass->id]));
        }

        return api_response(['id' => $groupClass->id], 'کلاس ثبت شد');
    }

    public function storeGroupClassSchedule(Request $request, GroupClass $groupClass)
    {
        $validatedData = $request->validate([
            'days' => 'required|array',
            'times' => 'required|array',
            'times.*' => ['regex:/^\d{2}:\d{2} - \d{2}:\d{2}$/'],
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $schedules = [];

        foreach ($validatedData['days'] as $day) {
            foreach ($validatedData['times'] as $time) {
                [$start_time, $end_time] = explode(' - ', $time);

                $scheduleData = [
                    'day' => $day,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                ];

                $groupClass->schedules()->create($scheduleData);
            }
        }

        $groupClass->update([
            'start_date' => $validatedData['start_date'],
            'end_date' => $validatedData['end_date'],
        ]);

        return api_response([],'ساعت ها ثبت شد');
    }
    public function price(Request $request, $group_classes_id)
    {

        $request->validate([
            'discount_type' => 'required|in:general,special,first_few,hourly',
            'discount_percentage' => 'required|integer|min:1|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'first_few_count' => 'nullable|integer|min:1',
            'special_day_id' => 'nullable|exists:special_day,id',
            'description' => 'nullable|string',
        ]);

        $discount = DiscountForGroupClass::updateOrCreate(
            ['group_classes_id' => $group_classes_id],
            $request->only([
                 'discount_type', 'discount_percentage', 'start_date', 'end_date', 'first_few_count', 'special_day_id', 'description'
            ])
        );

        GroupClass::where('id', $group_classes_id)->update(
            $request->only(['price_for_age', 'price_for_level', 'price_for_place'])
        );

        return api_response([],'قیمت ها ثبت شدند');
    }
    public function show_info($id)
    {
        $groupClass = GroupClass::with(['books', 'discount','schedules'])->find($id);

        if (!$groupClass) {
            return api_response(null, 'کلاس یافت نشد', 404);
        }

        return api_response([
            'group_class' => $groupClass,

        ], 'اطلاعات کلاس دریافت شد');
    }

    public function confirmGroupClass(Request $request, $group_class_id)
    {
        $groupClass = GroupClass::findOrFail($group_class_id);

        $groupClass->update([
            'status' => 'approved',
        ]);

        return api_response([], 'گروه کلاس با موفقیت تایید شد');
    }
    public function rejectGroupClass(Request $request, $group_class_id)
    {
        $groupClass = GroupClass::findOrFail($group_class_id);

        $groupClass->update([
            'status' => 'rejected',
        ]);

        return api_response([], 'گروه کلاس با موفقیت رد شد');
    }



}
