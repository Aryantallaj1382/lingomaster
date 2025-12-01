<?php

namespace App\Http\Controllers\Api\Students\exam;

use App\Http\Controllers\Controller;
use App\Models\Exam_question\Exam;
use App\Models\Exam_question\ExamStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Shetabit\Multipay\Drivers\Zarinpal\Zarinpal;

class ExamController extends Controller
{
    public function toggleLike(Request $request, $id)
    {
        $group = Exam::findOrFail($id);
        $user = auth()->user();

        if (!$user) {
            return api_response([], 'برای لایک کردن لاگین کنید', 401);
        }

        $like = $user->likes()
            ->where('likeable_id', $group->id)
            ->where('likeable_type', Exam::class)
            ->first();

        if ($like) {
            $like->delete();
            return api_response([], 'لایک حذف شد');
        }

        $group->likes()->create([
            'user_id' => $user->id,
        ]);

        return api_response([], 'لایک شد');
    }


    public function userExams(Request $request)
    {
        $exams = $request->user()->student
            ->exams()
            ->with('language:id,title')
            ->paginate(5);

        $mapped = $exams->getCollection()->map(function ($exam) {
            return [
                'id'               => $exam->id,
                'name'             => $exam->name,
                'language'         => $exam->language->title ?? null,
                'duration_minutes' => $exam->duration_minutes,
            ];
        });

        $exams->setCollection($mapped);

        return api_response($exams);
    }

    public function examDetail(int $id)
    {
        $exam = Exam::with([
            'skill:id,name',
            'language:id,title',
        ])->findOrFail($id);

        $responseData = [
            'id'               => $exam->id,
            'name'             => $exam->name,
            'description'      => $exam->description,
            'duration_minutes' => $exam->duration_minutes,
            'level'            => $exam->level,
            'type'             => $exam->type,
            'is_active'        => $exam->is_active,
            'skill'            => $exam->skill->name ?? null,
            'language'         => $exam->language->title ?? null,
        ];

        return api_response($responseData);
    }

    public function examQuestions ( Request $request , int $id )
    {


        $student = auth()->user()->student;

        $exam = $student->exams()->findOrFail($id);
        $examStudent = ExamStudent::where('exam_id', $exam->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $examStudent->increment('attempt_number');

        $relation = $exam->parts();

        if (!request('part')){
            $part=$relation->first();
            return api_response(['part_id' => $part->id, 'part_title' => $part->title , 'description' =>  $part->description , 'total_part' => $relation->count()], "part of {$part->title} the  {$exam->name} exam questions");
        }


        $part = $exam->parts()->findOrFail(request('part'));


        $questions = $part->questions()->with([
            'variants.options:id,question_variant_id,option_text,is_correct',
            'questionType:id,name',
            'medias:id,media_type,media_path,description',
        ])->get();

        $mappedQuestions = $questions->map(function ($question) {
            $base = [
                'id' => $question->id,
                'title' => $question->title ?? '',
                'description' => $question->question_text ?? '',
                'questionType' => $question->questionType->name ?? '',
                'multiple_correct' => $question->multiple_correct ?? false,
                'medias' => $question->medias()->get()->map(function ($media) {
                    return [
                        'media_type' => $media->media_type,
                        'media_path' => $media->media_path,
                        'description' => $media->description ?? '',
                    ];
                }),
            ];

            if ($question->questionType->name !== 'Blank question') {
                $base['variants'] = $question->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'question' => $variant->variant_text,
                        'options' => $variant->options->map(function ($opt) {
                            return [
                                'id' => $opt->id,
                                'text' => $opt->option_text,
                            ];
                        })->values(),
                    ];
                });
            } else {
                $base['short_answer'] = $question->short_answer;
            }

            return $base;
        });

        $api = [
            'part_id' => $part->id,
            'part_title' => $part->title,
            'duration' => $part->duration,
            'questions' => $mappedQuestions,
        ];

        return api_response($api, "part of {$part->title} the  {$exam->name} exam questions");

    }


    public function ExamsResults(Request $request)
    {
        $exams = $request->user()
            ->student
            ->exams()
            ->wherePivot('attempt_number', '!=' , 0)
            ->orderByPivot('finished_at', 'desc')
            ->paginate(10);

        $mapped = $exams->getCollection()->map(function ($exam) {
            return [
                'name'     => $exam->name,
                'pass'     => $exam->pivot->pass,
                'datetime' => $exam->pivot->finished_at,
                'score'    => $exam->pivot->score,
                'reportCardPath' => $exam->pivot->reportCardPath,
            ];
        });

        $exams->setCollection($mapped);

        return api_response($exams);
    }

    public function ExamResult(Request $request, int $id)
    {
        $exam = $request->user()
            ->student
            ->exams()
            ->where('id', $id)
            ->firstOrFail();

        $exam->load('language:id,title');

        $responseData = [
            'id'        => $exam->id,
            'name'      => $exam->name,
            'level'     => $exam->level,
            'type'      => $exam->type,
            'is_active' => $exam->is_active,
            'language'  => $exam->language->title ?? null,
            'score'     => $exam->pivot->score,
            'pass'      => $exam->pivot->pass,
            'datetime'  => $exam->pivot->finished_at,
        ];

        return api_response($responseData);
    }


    public function payExamReservation($reservationId)
    {

        $exam = Exam::where('id', $reservationId)
            ->where('payment_status', 'pending')
            ->firstOrFail();

        $amount = $exam->total_price;
        $description = "پرداخت آزمون: " . $exam->name;

        $paymentService = new zarinpal();
        $callback ='https://api.lingomasters.ir/zarin_callback';
        return $paymentService->pay($exam, $amount, $description , $callback);

    }


    public function zarin_callback(Request $request)
    {
        if (!$request->filled('Authority') || !$request->filled('Status'))
            return api_response([], 'خطا در پاسخ درگاه پرداخت', 403);

        $order_payment = Payment::where('authority', '=', $request->Authority)->first();


        if (blank($order_payment))
            return api_response([], 'اطلاعات پرداخت حذف شده است', 403);


        $amount =  $order_payment->amount;
        $authority = $order_payment->authority;
        $response = zarinpal()
            ->merchantId(config('zarinpal.merchant_id'))
            ->amount($amount)
            ->verification()
            ->authority($authority)
            ->send();
        $redirect_url = 'https://lingomasters.ir/group-class/success' ;

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $payable = $order_payment->payable;
            if ($payable instanceof Exam) {
                $payable->update([
                    'payment_status' => 'failed',
                ]);
            }

            return redirect($redirect_url);
        }
        $ref =  $response->referenceId();


        $payable = $order_payment->payable;


        if ($payable instanceof Exam) {
            $payable->update([
                'payment_status' => 'paid',
            ]);
            auth()->user()->student->exams()->syncWithoutDetaching([$payable->id => [
                'payment_status' => 'paid',
                'attempt_number' => 1,
            ]]);
        }

        $order_payment->update([
            'ref_id' =>$ref,
            'status' => 'paid',
        ]);



        return redirect($redirect_url);
    }


    public function TestCorrection (Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'answer' => 'required|array',
        ]);

        $exam = Exam::findOrFail($request->exam_id);

        $tests = $exam->questions()->whereHas('questionType', function ($query)  {
            $query->where('name','test');
        })->get();

        $TrueAnswer = collect();
        $FalseAnswer = collect();

        $examStudent = ExamStudent::where('exam_id', $exam->id)->where('student_id', auth()->user()->student->id)->firstOrFail();


        foreach ($tests as $test) {
            $questionId = $test->id;

            if (!isset($request->answer[$questionId])) {
                continue;
            }

            $selectedOptionId = $request->answer[$questionId];
            $option = $test->options()->find($selectedOptionId);

            if ($option && $option->is_correct) {
                $TrueAnswer->push($test);
                $examStudent->answers()->create([
                    'question_id' => $questionId ,
                    'is_correct' => true
                ]);
            } else {
                $FalseAnswer->push($test);
                $examStudent->answers()->create([
                    'question_id' => $questionId ,
                    'is_correct' => False
                ]);
            }
        }

        $mapQuestion = fn($q) => [
            'title' => $q->title,
            'question_text' => $q->question_text,
            'difficulty' => $q->difficulty,
        ];

        $mappedTrueAnswer = $TrueAnswer->map($mapQuestion);

        $mappedFalseAnswer = $FalseAnswer->map($mapQuestion);

        $score = round(($TrueAnswer->count() / max(count($tests), 1)) * 100);

        if ($score > 50){
            $examStudent->update([
                'pass' => true
            ]);
        }

        return api_response([
            'score' => $score ,
            'TrueAnswers' => $mappedTrueAnswer,
            'FalseAnswers' => $mappedFalseAnswer,
        ]);


    }






}
