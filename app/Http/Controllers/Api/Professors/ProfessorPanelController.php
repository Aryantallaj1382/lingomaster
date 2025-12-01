<?php

namespace App\Http\Controllers\Api\Professors;

use App\Helpers\zarinpal;
use App\Models\Financial\Payment;
use App\Models\Financial\Transaction;
use App\Models\GeographicRegion;
use App\Models\ProfessorPlanSubscription;
use App\Models\Professors\Notification;
use App\Models\Professors\WebinarReservation;
use App\Models\shop\Videos;
use Carbon\Carbon;
use Illuminate\Routing\Controller;  // حتماً از این استفاده کن
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Professors\Professor;
use App\Models\Professors\ProfessorAuthor;
use App\Models\Professors\ProfessorBlog;
use App\Models\Professors\ProfessorCertificate;
use App\Models\Professors\ProfessorEducation;
use App\Models\Professors\ProfessorHonor;
use App\Models\Professors\ProfessorRecord;
use App\Models\Professors\ProfessorTranslation1;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class ProfessorPanelController extends Controller
{
    public function uploadProfileImage(Request $request)
    {
        $id = auth()->user()->professor->id;
        $request->validate([
            'profile' => 'required',
        ]);

        $Professor = Professor::findOrFail($id);

        if ($Professor->profile) {
            Storage::delete('public/profiles/professor/' . basename($Professor->profile));
        }

        $imageName = time() . '.' . $request->profile->extension();
        $request->profile->move(public_path('profiles/professor'), $imageName);

        $Professor->profile = 'profiles/professor/' . $imageName;
        $Professor->save();
        $Professor->user->profile_image = 'profiles/professor/' . $imageName;
        $Professor->user->save();

        return api_response([],'ذخیره شد');
    }

    public function paid(Request $request)
    {
        $user = auth()->user()->professor;
        if ($user->is_paid == true) {
            return api_response([],'شما قبلا پرداخت کردید');
        }
        $professor = Professor::findOrFail($user->id);
        $description = "پرداخت رزرو استاد: " . $professor->full_name();

        $paymentService = new zarinpal();
        $callback ='https://api.lingomasters.ir/zarin_callback_paid';
        return $paymentService->pay($professor, 100000, $description , $callback);
    }
    public function zarin_callback_paid(Request $request)
    {
        if (!$request->filled('Authority') || !$request->filled('Status')) {
            return api_response([], 'خطا در پاسخ درگاه پرداخت', 403);
        }

        $order_payment = Payment::where('authority', $request->Authority)->first();

        if (blank($order_payment)) {
            return api_response([], 'اطلاعات پرداخت حذف شده است', 403);
        }

        $amount = $order_payment->amount;
        $authority = $order_payment->authority;

        $response = zarinpal()
            ->merchantId(config('zarinpal.merchant_id'))
            ->amount($amount)
            ->verification()
            ->authority($authority)
            ->send();

        $redirect_url = 'https://professor.lingomasters.ir/profile';

        // اگر پرداخت ناموفق بود
        if (!$response->success()) {
            $order_payment->update([
                'status' => 'failed',
            ]);

            $payable = $order_payment->payable;
            if ($payable instanceof Professor) {
                $payable->update([
                    'is_paid' => false,
                ]);

                $walletId = $payable->user->wallet->id;
                Transaction::create([
                    'type' => 'payment',
                    'amount' => $amount,
                    'description' => 'پرداخت ناموفق استاد',
                    'wallet_id' => $walletId,
                    'status' => 'rejected',
                ]);
            }

            return redirect($redirect_url);
        }

        // ✅ اگر پرداخت موفق بود:
        $ref = $response->referenceId();
        $payable = $order_payment->payable;

        if ($payable instanceof Professor) {
            $payable->update([
                'is_paid' => true,
            ]);

            $walletId = $payable->user->wallet->id;

            Transaction::create([
                'type' => 'payment',
                'amount' => $amount,
                'description' => 'پرداخت موفق رزرو استاد',
                'wallet_id' => $walletId,
                'status' => 'approved',
            ]);
        }

        $order_payment->update([
            'ref_id' => $ref,
            'status' => 'paid',
        ]);

        return redirect($redirect_url);
    }
    public function showProfessor(Request $request)
    {

        $user = auth()->user();
        if (!$user) {
            return api_response([], 'کاربر احراز هویت نشده است.', 401);
        }

        $language = $request->language_id;

        $professorId = $user->professor?->id;

        if (!$professorId) {
            return api_response([], 'استاد مرتبط با این کاربر یافت نشد.', 404);
        }

        $professor = Professor::with('user')
            ->select('user_id', 'id', 'mobile', 'national_code', 'email', 'age', 'professors_gender', 'place_of_birth', 'date_of_birth', 'identification_code', 'is_paid','last_name', 'first_name', 'status', 'profile','professor_code')
            ->find($professorId);

        if (!$professor) {
            return api_response([], 'استاد مورد نظر یافت نشد.', 404);
        }

        $plan = ProfessorPlanSubscription::where('language_id', $language)
            ->where('professor_id', $professorId)
            ->where('status', 'paid')
            ->first();

        $daysLeft = $plan ? Carbon::now()->diffInDays(Carbon::parse($plan->end_date), false) : null;
        $educationsRaw = $professor->education;

        $educations = $educationsRaw && $educationsRaw->isNotEmpty()
            ? $educationsRaw->map(function ($edu) {
                return [
                    'id' => $edu->id,
                    'date_graduation' => $edu->date_graduation,
                    'city' => $edu->city,
                    'university' => $edu->university,
                    'lastest_evidence' => $edu->lastest_evidence,
                    'lastest_evidence_file_path' => $edu->lastest_evidence_file_path ? asset('public/'.$edu->lastest_evidence_file_path) : (object)[],
                    'student' => $edu->is_student ,
                    'field' => $edu->field ?? '',
                    'university_name' => $edu->university_name ?? '',
                    'university_city' => $edu->university_city ?? '',
                    'student_cart_file_path' => $edu->student_cart_file_path ? asset('public/'.$edu->student_cart_file_path) : (object)[],
                ];
            })->values()
            : null;
        return api_response([
            'educations' => $educations,
            'id' => $professor->id,
            'user_id' => $professor->user_id,
            'mobile' => $professor->mobile,
            'national_code' => $professor->national_code,
            'email' => $professor->email,
            'professors_gender' => $professor->professors_gender,
            'place_of_birth' => $professor->place_of_birth,
            'date_of_birth' => $professor->date_of_birth,
            'identification_code' => $professor->professor_code,
            'first_name' => $professor->first_name,
            'last_name' => $professor->last_name,
            'status' => $professor->status,
            'is_paid'=>$professor->is_paid,
            'profile' => $professor->profile ? api::api . $professor->profile : null,
            'balance' => $user->wallet->balance ?? 0,
            'month' => $plan?->duration->months ?? null,
            'remind' => (int)$daysLeft,
            'plan_name' => $plan?->plan->name ?? null,
            'price' => $plan?->duration->price ?? null,
            'unseen' => Notification::where('user_id', $user->id)
                ->where('is_seen', false)
                ->exists() ? 1 : 0,
        ]);
    }

    public function updateProfessor(Request $request)
    {

        $professor = auth()->user()->professor->id;

        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable',
            'mobile' => 'nullable|string|size:11|unique:users,mobile,',
            'place_of_birth' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'national_code' => 'nullable|string|max:10|unique:professors,national_code,',
            'professors_gender' => 'nullable|string',
            'age' => 'nullable|integer|min:0',
        ], [], [
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'email' => 'ایمیل',
            'mobile' => 'شماره موبایل',
            'place_of_birth' => 'محل تولد',
            'date_of_birth' => 'تاریخ تولد',
            'national_code' => 'کد ملی',
            'professors_gender' => 'جنسیت',
            'age' => 'سن',
        ]);

        $professor = Professor::findOrFail($professor);
        $user = $professor->user;

        // Update the user data, preserving the existing values for missing fields
        $user->update([
            'first_name' => $validated['first_name'] ?? $user->first_name,  // Use existing value if not provided
            'last_name' => $validated['last_name'] ?? $user->last_name,  // Use existing value if not provided
            'email' => $validated['email'] ?? $user->email,  // Use existing value if not provided
            'mobile' => $validated['mobile'] ?? $user->mobile,  // Use existing value if not provided
        ]);

        // Update the professor data, preserving the existing values for missing fields
        $professor->update([
            'first_name' => $validated['first_name'] ?? $professor->first_name,  // Use existing value if not provided
            'last_name' => $validated['last_name'] ?? $professor->last_name,  // Use existing value if not provided
            'email' => $validated['email'] ?? $user->email,  // Use existing value if not provided
            'mobile' => $validated['mobile'] ?? $user->mobile,  // Use existing value if not provided
            'place_of_birth' => $validated['place_of_birth'] ?? $professor->place_of_birth,  // Use existing value if not provided
            'date_of_birth' => $validated['date_of_birth'] ?? $professor->date_of_birth,  // Use existing value if not provided
            'national_code' => $validated['national_code'] ?? $professor->national_code,  // Use existing value if not provided
            'professors_gender' => $validated['professors_gender'] ?? $professor->professors_gender,  // Use existing value if not provided
            'age' => $validated['age'] ?? $professor->age,  // Use existing value if not provided
        ]);

        return api_response([], 'اطلاعات اپدیت شد');
    }

    public function storeEducation(Request $request)
    {
        $professor = auth()->user()->professor;

        if (!$professor) {
            return api_response([], 'استاد یافت نشد.', 404);
        }

        $data = $request->validate([
            'educations' => 'required|array',
            'educations.*.id' => 'nullable',
            'educations.*.lastest_evidence' => 'nullable|string|max:255',
            'educations.*.university' => 'nullable|string|max:255',
            'educations.*.city' => 'nullable|string|max:255',
            'educations.*.field' => 'nullable|string|max:255',
            'educations.*.university_name' => 'nullable|string|max:255',
            'educations.*.university_city' => 'nullable|string|max:255',
            'educations.*.lastest_evidence_file_path' => 'nullable',
            'educations.*.student_cart_file_path' => 'nullable|file',
            'educations.*.date_graduation' => 'nullable|date',
        ]);

        DB::beginTransaction();
        try {
            foreach ($data['educations'] as $index => $educationData) {
                $educationData['professors_id'] = $professor->id;

                /* -------------------------
                    lastest_evidence_file_path
                --------------------------*/
                $lastest = $request->input("educations.$index.lastest_evidence_file_path");

                $file = $request->file("educations.$index.lastest_evidence_file_path");

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    // فایل جدید آپلود شده
                    $educationData['lastest_evidence_file_path'] = $file->store('education', 'public');
                } elseif (is_string($lastest) && !empty($lastest)) {
                    // مقدار string آمده، اگر شامل آدرس کامل بود باید حذف شود
                    $educationData['lastest_evidence_file_path'] = str_replace(url('/'), '', $lastest);
                }


                /* -------------------------
                    student_cart_file_path
                --------------------------*/
                $studentCart = $request->input("educations.$index.student_cart_file_path");

                $file = $request->file("educations.$index.student_cart_file_path");

                if ($file instanceof \Illuminate\Http\UploadedFile) {
                    $educationData['student_cart_file_path'] = $file->store('education', 'public');
                    $educationData['is_student'] = true;
                } elseif (is_string($studentCart) && !empty($studentCart)) {
                    // مقدار string — فقط مسیر را نگه می‌داریم
                    $educationData['student_cart_file_path'] = str_replace(url('/'), '', $studentCart);
                    $educationData['is_student'] = true;
                } else {
                    $educationData['is_student'] = false;
                }


                /* -------------------------
                    ساخت یا آپدیت رکورد
                --------------------------*/
                if (!empty($educationData['id'])) {
                    $education = ProfessorEducation::where('id', $educationData['id'])
                        ->where('professors_id', $professor->id)
                        ->first();

                    if ($education) {
                        $education->update($educationData);
                        continue;
                    }
                }

                ProfessorEducation::create($educationData);
            }

            DB::commit();
            return api_response([], 'با موفقیت ذخیره شد', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return api_response([], 'خطایی رخ داد: ' . $e->getMessage(), 500);
        }
    }


    public function showAddress(Request $request)
    {
        $professor = auth()->user()->professor->id;

        $professor = Professor::with('addresses.city', 'addresses.country')->findOrFail($professor);

        $address = $professor->addresses->first();


        // برای دسترسی به اولین آدرس

        return api_response([
            'postal_code' => $address->postal_code ?? null,
            'unit' => $address->unit ?? null,
            'title' => $address->title ?? null,
            'city_id' => $address->city_id ?? null,
            'country_id' => $address->country->id ?? null,
            'plate' => $address->plate ??null,
            'landline' => $address->landline??null,
            'district' => $address->district??null,
            'address' => $address->address??null,
            'longitude' => $address->longitude??null,
            'latitude' => $address->latitude??null,
            'city' => $address->city->name ?? null, // نام شهر
            'country' => $address->country->title ?? null, // نام کشور
        ]);
    }


    public function updateAddress(Request $request)
    {
        $professorId = auth()->user()->professor->id;

        $validated = $request->validate([
            'city_id' => 'nullable|exists:iran_cities,id',
            'country_id' => 'nullable|exists:countries,id',
            'postal_code' => 'nullable|string|max:10',
            'unit' => 'nullable|string|max:10',
            'plate' => 'nullable|string|max:20',
            'landline' => 'nullable|string|max:15',
            'district' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ], [], [
            'city' => 'شهر',
            'country' => 'کشور',
            'postal_code' => 'کد پستی',
            'unit' => 'واحد',
            'plate' => 'پلاک',
            'landline' => 'تلفن ثابت',
            'district' => 'منطقه',
            'address' => 'آدرس',
            'latitude' => 'عرض جغرافیایی',
            'longitude' => 'طول جغرافیایی',
        ]);


        $professor = Professor::findOrFail($professorId);



        $address = $professor->addresses()->first();

        if (!$address) {
            $address = $professor->addresses()->create($validated);
            return api_response([], 'آدرس جدید برای پروفسور ایجاد شد.');
        }

        $address->update($validated);

        return api_response([], 'آدرس پروفسور با موفقیت به‌روزرسانی شد');
    }

    public function showLanguage(Request $request)
    {
        $user = auth()->user();
        $professor = $user?->professor;
        $languages = Languages::with('accents')->get();

        return api_response([
            'languages' => $languages->map(function ($language) {
                return [
                    'id' => $language->id,
                    'language' => $language->title,
                    'flag' => api::api . $language->flag,
                    'accents' => $language->accents->isEmpty() ? null : $language->accents->map(function ($accent) {
                        return [
                            'id' => $accent->id,
                            'title' => $accent->title,
                        ];
                    }),
                ];
            }),

//            'professor_languages' => $professor
//                ? $professor->accents->map(function ($accent) {
//                    $language = $accent->language;
//
//                    return [
//                        'id' => $language->id,
//                        'language' => $language->title,
//                        'flag' => api::api . $language->flag,
//                        'accents' => [
//                            [
//                                'id' => $accent->id,
//                                'title' => $accent->title,
//                            ]
//                        ],
//                    ];
//                })
//                : [],
            'professor_languages' => $professor
                ? array_values($professor->accents->reduce(function ($carry, $accent) {
                    $language = $accent->language;
                    if (!isset($carry[$language->id])) {
                        $carry[$language->id] = [
                            'id' => $language->id,
                            'language' => $language->title,
                            'flag' => api::api . $language->flag,
                            'accents' => [],
                        ];
                    }

                    $carry[$language->id]['accents'][] = [
                        'id' => $accent->id,
                        'title' => $accent->title,
                    ];

                    return $carry;
                }, []))
                : [],
        ]);
    }
    public function updateLanguage(Request $request)
    {
        $professor = auth()->user()->professor;

        // اعتبارسنجی ورودی
        $validated = $request->validate([
            'accents' => 'nullable|array',
            'accents.*' => 'nullable|exists:accents,id',
            'language' => 'nullable|array',
            'language.*' => 'nullable|exists:languages,id',
        ], [], [
            'accents' => 'لهجه‌ها',
            'accents.*' => 'لهجه',
            'language' => 'زبان‌ها',
            'language.*' => 'زبان',
        ]);
        if (array_key_exists('accents', $validated) && is_array($validated['accents'])) {
            $professor->accents()->sync(array_filter($validated['accents']));
        }

        if (array_key_exists('language', $validated) && is_array($validated['language'])) {
            $professor->languages()->sync(array_filter($validated['language']));
        }


        return api_response([

            'professor_accents' => $professor->accents,
            'professor_languages' => $professor->languages,
        ],'زبان‌ها و لهجه‌ها با موفقیت به‌روزرسانی شدند');
    }



    public function showVideo(Request $request)
    {
        $professorId = auth()->user()->professor->id;

        $professor = Professor::with('about_video', 'teaching_example_video')->findOrFail($professorId);

        $aboutVideoUrl = $professor->about_video->first()?->path
            ? api::api . $professor->about_video->first()->path
            : null;

        $teachingExampleVideos = $professor->teaching_example_video->mapWithKeys(function ($video) {
            $language = $video->language;
            $videoUrl = $video->path
                ? api::api . $video->path
                : null;
            return [$language->title => $videoUrl];
        });

        return api_response([
            'about_video' => $aboutVideoUrl,
            'teaching_example_video' => $teachingExampleVideos,
        ]);
    }
    public function x()
    {
        $A = Videos::all();
        return api_response($A);

    }

    public function updateVideos(Request $request)
    {
        $professorId = auth()->user()->professor->id;

        $validated = $request->validate([
            'about_video' => 'nullable|file|max:20240',
            'videos' => 'nullable|array',
            'videos.*.language_id' => 'nullable|integer|exists:languages,id',
            'videos.*.teaching_example_video' => 'nullable|file|max:20240',
        ], [], [
            'about_video' => 'ویدئوی معرفی',
            'videos' => 'ویدئوها',
            'videos.*.language_id' => 'شناسه زبان',
            'videos.*.teaching_example_video' => 'ویدئوی نمونه تدریس',
        ]);

        $professor = Professor::findOrFail($professorId);

        if ($request->hasFile('about_video')) {
            $aboutVideoPath = $request->file('about_video')->store('videos/about_video_professor', 'public');

            if ($professor->about_video()->exists()) {
                foreach ($professor->about_video as $video) {
                    Storage::disk('public')->delete($video->path);
                    $video->delete();
                }
            }

            $professor->about_video()->create([
                'path' => $aboutVideoPath,
                'type' => 'about_video',
            ]);

        }

        if (!empty($validated['videos']) && is_array($validated['videos'])) {
            foreach ($validated['videos'] as $videoData) {
                $languageId = $videoData['language_id'];

                if (isset($videoData['teaching_example_video'])) {
                    $teachingVideoPath = $videoData['teaching_example_video']->store('videos/teaching_example', 'public');

                    if ($professor->teaching_example_video()->where('language_id', $languageId)->exists()) {
                        foreach ($professor->teaching_example_video()->where('language_id', $languageId)->get() as $video) {
                            Storage::disk('public')->delete($video->path);
                            $video->delete();
                        }
                    }

                    $professor->teaching_example_video()->create([
                        'path' => $teachingVideoPath,
                        'type' => 'teaching_example_video',
                        'language_id' => $languageId,
                    ]);
                }
            }
        }

        return api_response([], 'ویدیوهای پروفسور با موفقیت به‌روزرسانی شدند');
    }


    public function storeTranslation(Request $request)
    {
        $data = $request->validate([
            'translations' => 'required|array',
            'translations.*.name' => 'required|string|max:255',
            'translations.*.file' => 'required|file|max:2048',
            'translations.*.language_id' => 'required|exists:languages,id',
        ], [], [
            'translations' => 'ترجمه‌ها',
            'translations.*.name' => 'نام ترجمه',
            'translations.*.file' => 'فایل ترجمه',
            'translations.*.language_id' => 'شناسه زبان',
        ]);

        $professor = auth()->user()->professor;

        $translations = [];
        foreach ($data['translations'] as $translationData) {
            $translationData['professors_id'] = $professor->id;

            if (isset($translationData['file'])) {
                $originalName = pathinfo($translationData['file']->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $translationData['file']->getClientOriginalExtension();
                $timestamp = now()->timestamp;
                $newFileName = "{$professor->id}_{$timestamp}_{$originalName}.{$extension}";

                $filePath = $translationData['file']->storeAs('professor_file/translations', $newFileName, 'public');
                $translationData['file_path'] = $filePath;

                unset($translationData['file']);

            }

            $translations[] = ProfessorTranslation1::create($translationData);
        }

        return api_response([$translations], 'با موفقیت ذخیره شد');
    }


    public function storeAuthor(Request $request)
    {
        $data = $request->validate([
            'authors' => 'required|array',
            'authors.*.name' => 'required|string|max:255',
            'authors.*.file' => 'required|file|max:2048',
            'authors.*.language_id' => 'required|exists:languages,id',
        ], [], [
            'authors' => 'نویسندگان',
            'authors.*.name' => 'نام نویسنده',
            'authors.*.file' => 'فایل نویسنده',
            'authors.*.language_id' => 'شناسه زبان',
        ]);


        $professor = auth()->user()->professor;



        $authors = [];
        foreach ($data['authors'] as $authorData) {
            $authorData['professors_id'] = $professor->id;

            if (isset($authorData['file'])) {
                $originalName = pathinfo($authorData['file']->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $authorData['file']->getClientOriginalExtension();
                $timestamp = now()->timestamp;
                $newFileName = "{$professor->id}_{$timestamp}_{$originalName}.{$extension}";

                $filePath = $authorData['file']->storeAs('professor_file/author', $newFileName, 'public');
                $authorData['file_path'] = $filePath;

                unset($authorData['file']);

            }

            $authors[] = ProfessorAuthor::create($authorData);
        }

    return api_response([$authors],'با موفقیت ذخیره شد');

    }

    public function storeHonor(Request $request)
    {
        $data = $request->validate([
            'honors' => 'required|array',
            'honors.*.name' => 'required|string|max:255',
            'honors.*.honor' => 'required|string|max:255',
            'honors.*.file' => 'required|file|max:2048',
            'honors.*.language_id' => 'required|exists:languages,id',
        ], [], [
            'honors' => 'افتخارات',
            'honors.*.name' => 'نام افتخار',
            'honors.*.file' => 'فایل افتخار',
            'honors.*.language_id' => 'شناسه زبان',
        ]);


        $professor = auth()->user()->professor;

        $professor->honors()->delete();


        $honors = [];
        foreach ($data['honors'] as $honorData) {
            $honorData['professors_id'] = $professor->id;

            if (isset($honorData['file'])) {
                $originalName = pathinfo($honorData['file']->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $honorData['file']->getClientOriginalExtension();
                $timestamp = now()->timestamp;
                $newFileName = "{$professor->id}_{$timestamp}_{$originalName}.{$extension}";

                $filePath = $honorData['file']->storeAs('professor_file/honor', $newFileName, 'public');
                $honorData['file_path'] = $filePath;

                unset($honorData['file']);

            }

            $honors[] = ProfessorHonor::create($honorData);
        }

        return api_response([$honors],'با موفقیت ذخیره شد');

    }

    public function storeBlog(Request $request)
    {
        $data = $request->validate([
        'blogs' => 'required|array',
        'blogs.persian_blogs' => 'nullable|array',
        'blogs.persian_blogs.*.name' => 'nullable|string|max:255',
        'blogs.persian_blogs.*.file' => 'nullable|file|max:2048',
        'blogs.persian_blogs.*.language_id' => 'required|exists:languages,id',

        'blogs.non_persian_blogs' => 'nullable|array',
        'blogs.non_persian_blogs.*.name' => 'nullable|string|max:255',
        'blogs.non_persian_blogs.*.file' => 'nullable|file|max:2048',
        'blogs.non_persian_blogs.*.language_id' => 'required|exists:languages,id',
    ], [], [
        'blogs' => 'وبلاگ‌ها',
        'blogs.persian_blogs' => 'وبلاگ‌های فارسی',
        'blogs.persian_blogs.*.name' => 'نام وبلاگ فارسی',
        'blogs.persian_blogs.*.file' => 'فایل وبلاگ فارسی',
        'blogs.persian_blogs.*.language_id' => 'شناسه زبان وبلاگ فارسی',

        'blogs.non_persian_blogs' => 'وبلاگ‌های غیر فارسی',
        'blogs.non_persian_blogs.*.name' => 'نام وبلاگ غیر فارسی',
        'blogs.non_persian_blogs.*.file' => 'فایل وبلاگ غیر فارسی',
        'blogs.non_persian_blogs.*.language_id' => 'شناسه زبان وبلاگ غیر فارسی',
    ]);


        $professor = auth()->user()->professor;


        $professor->blogs()->delete();

        $blogs = [];

        if (isset($data['blogs']['persian_blogs'])) {
            foreach ($data['blogs']['persian_blogs'] as $blogData) {
                $blogData['professors_id'] = $professor->id;
                $blogData['is_persian'] = true;

                if (isset($blogData['file'])) {
                    $originalName = pathinfo($blogData['file']->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $blogData['file']->getClientOriginalExtension();
                    $timestamp = now()->timestamp;
                    $newFileName = "{$professor->id}_{$timestamp}_{$originalName}.{$extension}";

                    $filePath = $blogData['file']->storeAs('professor_file/blog', $newFileName, 'public');
                    $blogData['file_path'] = $filePath;

                    unset($blogData['file']);

                }

                $blogs[] = ProfessorBlog::create($blogData);
            }
        }

        if (isset($data['blogs']['non_persian_blogs'])) {
            foreach ($data['blogs']['non_persian_blogs'] as $blogData) {
                $blogData['professors_id'] = $professor->id;
                $blogData['is_persian'] = false;

                if (isset($blogData['file'])) {
                    $originalName = pathinfo($blogData['file']->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $blogData['file']->getClientOriginalExtension();
                    $timestamp = now()->timestamp;
                    $newFileName = "{$professor->id}_{$timestamp}_{$originalName}.{$extension}";

                    $filePath = $blogData['file']->storeAs('professor_file/blog', $newFileName, 'public');
                    $blogData['file_path'] = $filePath;

                    unset($blogData['file']);

                }

                $blogs[] = ProfessorBlog::create($blogData);
            }
        }

        return api_response($blogs, 'با موفقیت ذخیره شد');
    }

    public function storeCertificate(Request $request)
    {
        $data = $request->validate([
            'certificates' => 'required|array',
            'certificates.in_iran.*.name' => 'nullable|string|max:255',
            'certificates.in_iran.*.institution_name' => 'nullable|string|max:255',
            'certificates.in_iran.*.file' => 'nullable|file|max:2048',
            'certificates.out_iran.*.name' => 'nullable|string|max:255',
            'certificates.out_iran.*.institution_name' => 'nullable|string|max:255',
            'certificates.out_iran.*.file' => 'nullable|file|max:2048',
        ], [], [
            'certificates' => 'مدارک',
            'certificates.in_iran' => 'مدارک داخلی ایران',
            'certificates.in_iran.*.name' => 'نام مدرک داخلی ایران',
            'certificates.in_iran.*.institution_name' => 'نام مؤسسه داخلی ایران',
            'certificates.in_iran.*.file' => 'فایل مدرک داخلی ایران',

            'certificates.out_iran' => 'مدارک خارجی',
            'certificates.out_iran.*.name' => 'نام مدرک خارجی',
            'certificates.out_iran.*.institution_name' => 'نام مؤسسه خارجی',
            'certificates.out_iran.*.file' => 'فایل مدرک خارجی',
        ]);


        $professor = auth()->user()->professor;

        $professor->certificates()->delete();


        $certificates = [];

        foreach (['in_iran' => true, 'out_iran' => false] as $key => $inIranValue) {
            if (!empty($data['certificates'][$key])) {
                foreach ($data['certificates'][$key] as $certificateData) {
                    $certificateData['professors_id'] = $professor->id;
                    $certificateData['in_iran'] = $inIranValue;


                    if (isset($certificateData['file'])) {
                        $originalName = pathinfo($certificateData['file']->getClientOriginalName(), PATHINFO_FILENAME);
                        $extension = $certificateData['file']->getClientOriginalExtension();
                        $timestamp = now()->timestamp;
                        $newFileName = "{$professor->id}_{$timestamp}_{$originalName}.{$extension}";

                        $filePath = $certificateData['file']->storeAs('professor_file/certificate', $newFileName, 'public');
                        $certificateData['file_path'] = $filePath;


                        unset($certificateData['file']);

                    }

                    $certificates[] = ProfessorCertificate::create($certificateData);
                }
            }
        }

        return api_response([$certificates],'با موفقیت ذخیره شد');
    }

    public function storeRecord(Request $request)
    {
        $professor = auth()->user()->professor;

        $data = $request->validate([
            'records' => 'required|array',
            'records.*' => 'array',
            'records.*.*.start_date' => 'nullable|date',
            'records.*.*.end_date' => 'nullable|date',
            'records.*.*.platform' => 'nullable|string|max:255',
            'records.*.*.city' => 'nullable|string|max:255',
            'records.*.*.uni' => 'nullable|string|max:255',
            'records.*.*.language_id' => 'required',
        ], [], [
            'records' => 'سوابق',
            'records.*' => 'سوابق فردی',
            'records.*.*.start_date' => 'تاریخ شروع',
            'records.*.*.end_date' => 'تاریخ پایان',
            'records.*.*.platform' => 'پلتفرم',
            'records.*.*.city' => 'شهر',
            'records.*.*.uni' => 'دانشگاه',
            'records.*.*.language_id' => 'شناسه زبان',
        ]);

        $professor->records()->delete();

        $records = [];

        foreach ($data['records'] as $type => $typeRecords) {
            foreach ($typeRecords as $recordData) {
                $recordData['type'] = $type;
                $recordData['professors_id'] = $professor->id;

                $records[] = ProfessorRecord::create($recordData);
            }
        }

        return api_response($records , 'با موفقیت ذخیره شد');
    }

    public function specialties(Request $request)
    {
        $professorId = auth()->user()->professor->id;

        $translations = ProfessorTranslation1::where('professors_id', $professorId)
            ->select('name', 'language_id')
            ->get();

        $blogs = ProfessorBlog::where('professors_id', $professorId)
            ->select('name', 'is_persian', 'language_id')
            ->get();

        $authors = ProfessorAuthor::where('professors_id', $professorId)
            ->select('name', 'language_id')
            ->get();

        $honors = ProfessorHonor::where('professors_id', $professorId)
            ->select('name', 'language_id')
            ->get();

        $certificates = ProfessorCertificate::where('professors_id', $professorId)
            ->select('name', 'in_iran', 'language_id')
            ->get();

        $records = ProfessorRecord::where('professors_id', $professorId)
            ->get();

        $professor = auth()->user()->professor;
        $languages = $professor->languages()
            ->withPivot('id as pivot_id')
            ->orderBy('pivot_id')
            ->select('languages.id', 'languages.title', 'languages.flag')
            ->get();

        $groupedData = $languages->map(function ($language) use ($translations, $blogs, $authors, $honors, $certificates, $records) {
            return [
                'language_id' => $language->id,
                'language_title' => $language->title,
                'language_flag' => api::api.$language->flag,
                'translations' => $translations->where('language_id', $language->id)->values(),
                'blogs' => [
                    'persian' => $blogs->where('language_id', $language->id)->where('is_persian', 1)->values(),
                    'english' => $blogs->where('language_id', $language->id)->where('is_persian', 0)->values(),
                ],
                'authors' => $authors->where('language_id', $language->id)->values(),
                'honors' => $honors->where('language_id', $language->id)->values(),
                'certificates' => [
                    'in_iran' => $certificates->where('language_id', $language->id)->where('in_iran', 1)->values(),
                    'out_iran' => $certificates->where('language_id', $language->id)->where('in_iran', 0)->values(),
                ],
                'records' => $records->where('language_id', $language->id)->groupBy('type')->map(function ($typeRecords, $type) {
                    $totalDuration = 0;

                    foreach ($typeRecords as $record) {
                        if ($record->start_date && $record->end_date) {
                            $startDate = \Carbon\Carbon::parse($record->start_date);
                            $endDate = \Carbon\Carbon::parse($record->end_date);

                            $duration = $endDate->diff($startDate);
                            $totalDuration += $duration->y + ($duration->m / 12) + ($duration->d / 365);
                        }
                    }

                    if ($totalDuration < 1) {
                        return [
                            'type' => $type,
                            'total_duration' => 'کمتر از 1',
                        ];
                    } else {
                        return [
                            'type' => $type,
                            'total_duration' => round($totalDuration),
                        ];
                    }
                })->values(),
            ];
        });

        // پاسخ به کاربر
        return api_response($groupedData, 'Data fetched successfully.');
    }




    public function regin()
    {
        $r = GeographicRegion::all();
        return api_response($r);
    }

    public function pro_regin(Request $request)
    {
        $professorId = auth()->user()->professor->id;

        $professor = Professor::with('geographicRegions')->findOrFail($professorId);

        return api_response([
            'regions' => $professor->geographicRegions->map(function ($region) {
                return [
                    'id'   => $region->id,
                    'name' => $region->title,
                ];
            })
        ], 'مناطق انتخاب شده توسط استاد', 200);
    }

    public function storeProfessorRegions(Request $request)
    {
        $data = $request->validate([
            'region_ids'   => 'required|array',
            'region_ids.*' => 'exists:geographic_regions,id',
        ]);

        $professorId = auth()->user()->professor->id;

        $professor = Professor::findOrFail($professorId);

        $professor->geographicRegions()->sync($data['region_ids']);

        return api_response(null, 'مناطق با موفقیت ثبت شدند', 200);
    }

}
