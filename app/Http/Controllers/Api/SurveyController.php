<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Professors\Professor;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\WorkShopReservation;
use Illuminate\Http\Request;
use App\Models\SurveyQuestion;
use App\Models\SurveyResponse;
use App\Models\SurveyResponseItem;
use App\Models\GroupClassReservation;
use App\Models\PrivetClassReservation;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    protected function getModelFromType($type)
    {
        return match ($type) {
            'group' => GroupClassReservation::class,
            'private' => PrivetClassReservation::class,
            'webinar' => WebinarReservation::class,
            'workshop' => WorkShopReservation::class,
            default => abort(404, 'Invalid class type'),
        };
    }

    protected function getClassTypeForSurveyQuestions($type)
    {
        return match ($type) {
            'group' => 'group_online', // یا 'group_offline' بسته به نوع
            'private' => 'private_online', // یا 'private_offline'
            'webinar' => 'webinar',
            'workshop' => 'workshop',
            default => abort(404, 'Invalid class type'),
        };
    }

    public function show($type, $reservation_id)
    {
        $model = $this->getModelFromType($type);
        $reservation = $model::findOrFail($reservation_id);

        if ($reservation->student_id !== auth()->user()->student->id) {
            abort(403, 'Unauthorized');
        }

        $existing = $reservation->surveyResponse;

        if ($existing && $existing->submitted_at) {
            return response()->json([
                'already_submitted' => true,
                'message' => 'You have already submitted the survey.'
            ]);
        }

        $classType = $this->getClassTypeForSurveyQuestions($type);
        $questions = SurveyQuestion::where('class_type', $classType)
            ->orderBy('order')
            ->get(['id', 'question', 'order']);

        return api_response($questions);
    }

    public function store(Request $request, $type, $reservation_id)
    {
        $model = $this->getModelFromType($type);
        $reservation = $model::findOrFail($reservation_id);

        if ($reservation->student_id !== auth()->user()->student->id) {
            abort(403, 'وارد شوید');
        }

        if ($reservation->surveyResponse && $reservation->surveyResponse->submitted_at) {
            return api_response([],'شما قبلا نظر خود را ثبت کرده اید');
        }

        $validated = $request->validate([
            'answers' => 'required|array|size:10',
            'answers.*.question_id' => 'required|exists:survey_questions,id',
            'answers.*.rating' => 'required|integer|min:1|max:5',
        ]);

        $response = $reservation->surveyResponse ?: $reservation->surveyResponse()->create([
            'student_id' => Auth::id(),
        ]);

        foreach ($validated['answers'] as $answer) {
            SurveyResponseItem::create([
                'survey_response_id' => $response->id,
                'survey_question_id' => $answer['question_id'],
                'rating' => $answer['rating'],
            ]);
        }

        $response->update([
            'submitted_at' => now(),
        ]);

        return api_response([],'با موفقیت نظر شما ثبت شد');
    }

    public function professorSurveyStats()
    {
        $p = \auth()->user()->professor->id;
        $results = $this->getProfessorSurveyAveragesByClassType($p);

        return api_response($results, 'میانگین امتیازها با موفقیت محاسبه شد');
    }


    public function getProfessorSurveyAveragesByClassType($professorId): array
    {
        $classTypes = [
            'private' => [
                'model' => \App\Models\PrivetClassReservation::class,
                'reservation_table' => 'private_class_reservations',
                'class_table' => null, // نیازی نیست
                'relation_column' => null, // نیازی نیست
                'professor_column' => 'professor_id', // مستقیماً در همین جدول
            ],
            'group' => [
                'model' => GroupClassReservation::class,
                'reservation_table' => 'group_class_reservations',
                'class_table' => 'group_classes',
                'relation_column' => 'group_class_id',
                'professor_column' => 'professors_id', // مستقیماً در همین جدول

            ],
            'webinar' => [
                'model' => WebinarReservation::class,
                'reservation_table' => 'webinar_reservations',
                'class_table' => 'webinars',
                'relation_column' => 'webinar_id',
                'professor_column' => 'professors_id', // مستقیماً در همین جدول

            ],
            'workshop' => [
                'model' => WorkShopReservation::class,
                'reservation_table' => 'work_shop_reservations',
                'class_table' => 'work_shops',
                'relation_column' => 'work_shop_id',
                'professor_column' => 'professors_id', // مستقیماً در همین جدول

            ],
        ];


        $results = [];
        $totalSum = 0;
        $totalCount = 0;
        foreach ($classTypes as $key => $info) {
            $query = DB::table('survey_response_items')
                ->join('survey_responses', 'survey_response_items.survey_response_id', '=', 'survey_responses.id')
                ->join($info['reservation_table'], function ($join) use ($info) {
                    $join->on('survey_responses.surveyable_id', '=', "{$info['reservation_table']}.id")
                        ->where('survey_responses.surveyable_type', '=', $info['model']);
                });

            if (!empty($info['class_table']) && !empty($info['professor_column'])) {
                $query->join($info['class_table'], "{$info['reservation_table']}.{$info['relation_column']}", '=', "{$info['class_table']}.id")
                    ->where("{$info['class_table']}.{$info['professor_column']}", $professorId);
            } elseif (!empty($info['professor_column'])) {
                $query->where("{$info['reservation_table']}.{$info['professor_column']}", $professorId);
            }


            $average = $query->avg('survey_response_items.rating');
            $count = $query->count();
            $percent = $average ? round(($average / 5) * 100, 2) : 0;

            $results[$key] = $percent;

            $totalSum += $average * $count;
            $totalCount += $count;
        }

        $totalAverage = $totalCount > 0 ? $totalSum / $totalCount : 0;
        $results['total_average'] = $totalAverage ? round(($totalAverage / 5) * 100, 2) : 0;
        $results['total_count'] = $totalCount / 10;
        $p = Professor::find($professorId);
        $results['experience'] = $p->experience;
        return $results;
    }

}
