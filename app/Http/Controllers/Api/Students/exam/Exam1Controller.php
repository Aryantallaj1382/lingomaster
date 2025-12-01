<?php

namespace App\Http\Controllers\Api\Students\exam;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Exam_question\Exam;
use App\Models\Exam_question\ExamReservation;
use App\Models\Exam_question\ExamStudent;
use App\Models\Financial\Payment;
use App\Models\Financial\Transaction;
use App\Models\Professors\Webinar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Shetabit\Multipay\Drivers\Zarinpal\Zarinpal;

class Exam1Controller extends Controller
{
    public function examDetailLevelSet(int $id)
    {
        $exam = Exam::with([
            'skill:id,name',
            'language:id,title',
        ])->where('isLevelSet' , '=',1)->findOrFail($id);
        $examData = $exam->toArray();
        unset($examData['skill_id']);
        unset($examData['language_id']);
        unset($examData['QuestionsCount']);
        unset($examData['is_active']);
        $examData['skill'] = optional($exam->skill)->name;
        $examData['language'] = optional($exam->language)->title;
        $examData['exams'] = Exam::where('id' ,'!=' , $id)->select('id' ,'image')->get();
        return api_response($examData );
    }

    public function userExams(Request $request)
    {
        $exams = Exam::query();

        if ($search = $request->input('search')) {
            $exams->where('name', 'like', '%' . $search . '%');
        }

        // فیلتر بر اساس زبان
        if ($language = $request->input('language')) {
            $exams->whereHas('language', function ($query) use ($language) {
                $query->where('title', $language);
            });
        }

        // فیلتر بر اساس خارجی بودن آزمون
        if (!is_null($request->input('Is_foreign'))) {
            $exams->where('is_foreign', $request->input('Is_foreign'));
        }

        // فیلتر نوع آزمون
        if ($type = $request->input('type')) {
            $exams->where('type', $type);
        }

        // فیلتر سطح تحصیلی
        if ($level = $request->input('level')) {
            $exams->where('level_education', $level);
        }

        // صفحه‌بندی نتایج
        $paginated = $exams->paginate(10); // می‌تونی 10 رو تغییر بدی به هر تعدادی که خواستی

        // مپ کردن دیتاهای داخل صفحه
        $paginated->getCollection()->transform(function ($exam) {
            return [
                'id' => $exam->id,
                'image' => $exam->image,
            ];
        });

        return api_response($paginated);
    }


    public function examDetail(int $id)
    {
        $exam = Exam::with([
            'skill:id,name',
            'language:id,title',
        ])->findOrFail($id);

        $examData = $exam->toArray();
        unset($examData['skill_id']);
        unset($examData['language_id']);
        unset($examData['QuestionsCount']);
        unset($examData['is_active']);
        $examData['skill'] = optional($exam->skill)->name;
        $examData['language'] = optional($exam->language)->title;
        $examData['exams'] = Exam::where('id' ,'!=' , $id)->get();
        return api_response($examData );
    }


    public function examQuestions(Request $request,$id)
    {
        $student = auth()->user()->student;

        $exam = Exam::find($id);
        $examStudent = ExamStudent::where('exam_id', $id)
            ->where('student_id', $student->id)
            ->first();

        if (!$exam)
        {
            return api_response([],'چیزی یافت نشد');
        }
        if (!$examStudent)
        {
            return api_response([],'چیزی یافت نشد');
        }

        $examStudent->increment('attempt_number');

        $relation = $exam->parts();

        if (!request('part')){
            $part = $relation->first();
            return api_response(['part_id' => $part->id, 'part_title' => $part->title , 'description' =>  $part->description , 'total_part' => $relation->count()], "part of {$part->title} the  {$exam->name} exam questions");
        }


        $part = $exam->parts()->where('number' ,$request->part)->first();


        $questions = $part->questions()->get();

        $mappedQuestions = $questions->map(function ($question) {
            $base = [
                'id' => $question->id,
                'title' => $question->title ?? '',
                'description' => $question->question_text ?? '',
                'questionType' => 'reading',
                'multiple_correct' => $question->multiple_correct ?? false,
                'medias' => $question->medias->map(function ($media) {
                    return [
                        'media_type' => $media->media_type,
                        'media_path' => $media->media_path,
                        'description' => $media->description ?? '',
                    ];
                }),
            ];

            if ($question->questionType->id != 1) {
                $base['variants'] = $question->variants->map(function ($variant) use ($question) {
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
            'questionType' => 'Cloze test',
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


    public function examCheckout( int $id)
    {
        $exam = Exam::with([
            'language:id,title',
        ])->findOrFail($id);

        $user = auth()->user();

        $responseData = [
            'id'               => $exam->id,
            'exam_name'             => $exam->name,
            'user_name'           => $user->first_name . ' ' . $user->last_name ,
            'language'         => $exam->language?->title,
            'age'              => $exam->AgeLevel,
            'Difficulty'       => $exam->Difficulty ,
            'price'               => $exam->price,
            'image'               => $exam->image,
        ];

        return api_response($responseData);
    }

    public function pay_group_reservation(Request $request,$reservationId)
    {

        $webinar = exam::findOrFail($reservationId);
        $price = $webinar->price;

        $reservation = ExamReservation::firstOrCreate(
            [
                'exam_id'    => $reservationId,
                'student_id'    => auth()->user()->student->id,
                'payment_status'=> 'pending',
                'discount_code' => $request->code ?? null ,
                'final_price'=> $price,
            ]
        );
        $group = $reservation->exam;



        $description = "پرداخت رزرو ازمون " . $group->name;

        $paymentService = new \App\Helpers\zarinpal();
        $callback ='https://api.lingomasters.ir/zarin_callback_exam';
        return $paymentService->pay($reservation, $price, $description , $callback);
    }

    public function zarin_callback_exam(Request $request)
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
        $redirect_url = 'https://lingomasters.ir/wallet/wallet' ;

        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);
            $payable = $order_payment->payable;
            if ($payable instanceof ExamReservation) {
                $payable->update([
                    'payment_status' => 'failed',
                ]);
                $walletId = $payable->student->user->wallet->id;
                Transaction::create([
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => "پرداخت ناموفق رزرو ازمون" ,
                    'wallet_id' => $walletId,
                    'status' => 'rejected',
                ]);
            }

            return redirect($redirect_url);
        }
        $ref =  $response->referenceId();
        $payable = $order_payment->payable;
        if ($payable instanceof ExamReservation) {
            $payable->update([
                'payment_status' => 'paid',
                'status'=> 'reserved'
            ]);
            $walletId = $payable->student->user->wallet->id;

            Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'description' => "پرداخت موفق رزرو ازمون" ,
                'wallet_id' =>$walletId,
                'status' => 'approved',
            ]);
        }

        $order_payment->update([
            'ref_id' =>$ref,
            'status' => 'paid',
        ]);



        return redirect($redirect_url);
    }

    public function pay_private_reservation_Wallet(Request $request,$reservationId)
    {
        $wallet = auth()->user()->wallet;
        $class = Exam::findOrFail($reservationId);
        $price = $class->price ?? 0;

        $reservation = ExamReservation::firstOrCreate(
            [
                'exam_id'    => $reservationId,
                'student_id'    => auth()->user()->student->id,
                'payment_status'=> 'pending',
                'status'=> 'pending',
                'discount_code' => $request->code ?? null ,
                'final_price'=> $price,            ]
        );
        if (!$reservation){
            return api_response(null, 'چیزی یافت نشد', 404);
        }
        $is_paid = ExamReservation::where('exam_id', $reservationId)->where('student_id', auth()->user()->student->id)
            ->where('payment_status', 'paid')->exists();
        if ($is_paid){
            return api_response([] , 'شما قبلا این کلاس را رزرو کرده اید',401);
        }
        $user_id = auth()->user()->id;

        $now = now()->format('His'); // ساعت به صورت 143251
        $rawString = $reservationId . '_' . $user_id . '_' . $now;

        $authority = crc32($rawString); // خروجی عدد صحیح


        $description = "پرداخت رزرو وبینار " ;

        $pa = $reservation->payment()->create([
            'user_id' => auth()->id(),
            'gateway_id' => 2,
            'amount' => $price,
            'authority' =>$authority,
            'status' => 'pending',
            'description' => $description,
        ]);
        $ta =  Transaction::create([
            'type' => 'payment',
            'amount' => $price,
            'description' => $description,
            'wallet_id' => $wallet->id,
            'status' => 'Pending',
        ]);
        if ($wallet->balance > $price) {
            $wallet->balance -= $price;
            $wallet->save();
            $ta->update([
                'status' => 'approved',
            ]);
            $pa->update([
                'status' => 'paid',
            ]);
            $reservation->update([
                'payment_status' =>'paid',
                'status' =>'reserved',
            ]);
            return api_response([],'پرداخت انجام شد و کلاس برای شما با موفقیت رزرو شد');

        }
        else{
            $ta->update([
                'status' => 'rejected',
            ]);
            $pa->update([
                'status' => 'failed',
            ]);

            return api_response([],' موجودی شما برای پرداخت این کلاس کافی نمیباشد' , 400 );
        }


    }


    public function comment(Request $request, $groupClassId)
    {
        $request->validate([
            'body' => 'nullable|string|min:1|max:400', // حداقل طول 1 برای جلوگیری از رشته خالی
            'video_url' => 'nullable|file',
            'voice_url' => 'nullable|file',
        ], [
            'body.min' => 'توضیحات نباید خالی باشد.',
            'body.required_without_all' => 'توضیحات یا ویدیو یا ویس باید ارسال شود.',
            'video_url.required_without_all' => 'توضیحات یا ویدیو یا ویس باید ارسال شود.',
            'voice_url.required_without_all' => 'توضیحات یا ویدیو یا ویس باید ارسال شود.',
        ], [
            'body' => 'توضیحات',
            'video_url' => 'ویدیو',
            'voice_url' => 'ویس',
        ]);

        $groupClass = exam::findOrFail($groupClassId);

        $videoUrl = null;
        $voiceUrl = null;

        if ($request->hasFile('video_url')) {
            $videoPath = $request->file('video_url')->store('comment/videos', 'public');
            $videoUrl = $videoPath;
        }
        if ($request->hasFile('voice_url')) {
            $voicePath = $request->file('voice_url')->store('comment/voices', 'public');
            $voiceUrl = $voicePath;
        }
        $comment = $groupClass->comments()->create([
            'user_id' => auth()->user()->id,
            'body' => $request->body,
            'video_url' => $videoUrl,
            'voice_url' => $voiceUrl,
        ]);

        return api_response([
            'id' => $comment->id,
            'body' => $comment->body,
            'video_url' => $videoUrl ? asset($videoUrl) : null,
            'voice_url' => $voiceUrl ? asset($voiceUrl) : null,
            'created_at' => $comment->created_at->diffForHumans(),
        ], 'نظر با موفقیت ثبت شد');
    }

    public function showComments(Request $request, $groupClassId)
    {
        $user_id = auth()->id();

        $groupClass = exam::find($groupClassId);
        if (!$groupClass) {
           return api_response([],'کامنتی یافت نشد');
        }


        $query = $groupClass->comments()->with([
            'user.student',
        ])->where('admin_status', 'approved');

        if ($user_id) {
            $query->with([
                'likes' => function ($q) use ($user_id) {
                    $q->where('user_id', $user_id);
                },
                'dislikes' => function ($q) use ($user_id) {
                    $q->where('user_id', $user_id);
                },
            ]);
        }

        if ($request->has('type')) {
            $type = $request->input('type');

            if ($type == 'text') {
                $query->whereNull('video_url')->whereNull('voice_url');
            } elseif ($type == 'video') {
                $query->whereNotNull('video_url')->whereNull('body');
            } elseif ($type == 'voice') {
                $query->whereNotNull('voice_url')->whereNull('body');
            }
        }

        $comments = $query->latest()->paginate();

        $comments->getCollection()->transform(function ($comment) use ($user_id) {
            return [
                'id' => $comment->id,
                'content' => $comment->body ?? null,
                'video_url' => $comment->video_url ? $comment->video_url : null,
                'voice_url' => $comment->voice_url ? $comment->voice_url : null,
                'created_at' => $comment->created_at->toDateTimeString(),
                'user' => [
                    'user_name' => optional($comment->user->student)->full_name() ?? null,
                    'image' => optional($comment->user->student)->profile
                        ? asset(api::api . $comment->user->student->profile)
                        : null,
                ],
                'likes_count' => $comment->likes->count(),
                'dislikes_count' => $comment->dislikes->count(),
                'is_like' => $user_id ? $comment->likes->isNotEmpty() : false,
                'is_dislike' => $user_id ? $comment->dislikes->isNotEmpty() : false,
            ];
        });

        return api_response($comments, 'ok');
    }
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

}
