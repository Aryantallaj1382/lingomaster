<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Exam;
use App\Models\Languages;
use App\Models\Like;
use App\Models\Professors\BookLearning;
use App\Models\Professors\GroupClass;
use App\Models\Professors\GroupClassBook;
use App\Models\Professors\GroupClassReservation;
use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\Professor;
use App\Models\Professors\Webinar;
use App\Models\Professors\WebinarReservation;
use App\Models\Professors\WorkShop;
use App\Models\Professors\WorkShopReservation;
use App\Models\Student\Student;
use App\Models\Student\StudentAddress;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use phpseclib3\Crypt\EC\Curves\nistb233;
use Storage;
use function Symfony\Component\Translation\t;

class StudentPanelController extends Controller
{

    public function uploadProfileImage(Request $request)
    {
        $id = auth()->user()->student->id;
        $request->validate([
            'profile' => 'required',
        ]);

        $student = Student::findOrFail($id);

        if ($student->profile) {
            Storage::delete('public/profiles/students/' . basename($student->profile));
        }

        $imageName = time() . '.' . $request->profile->extension();
        $request->profile->move(public_path('profiles/students'), $imageName);

        $student->profile = 'profiles/students/' . $imageName;
        $student->save();
        $student->user->profile_image = 'profiles/students/' . $imageName;
        $student->user->save();
        return api_response([], 'ذخیره شد');
    }

    public function updateLanguage(Request $request)
    {
        $student = auth()->user()->student;

        $validated = $request->validate([
            'language' => 'nullable|array',
            'language.*' => 'nullable|exists:languages,id',
        ], [], [
            'language' => 'زبان‌ها',
            'language.*' => 'زبان',
        ]);

        if ($student && isset($validated['language'])) {
            $student->languages()->sync($validated['language']);
        }

        return api_response([
            $validated,
        ], 'زبان‌ها با موفقیت به‌روزرسانی شدند');
    }


    public function showLanguage(Request $request)
    {


        $languages = Languages::with('accents')->get();

        if ( auth()->user()) {
            $student = auth()->user()->student;

            $s = $student->languages->map(function ($accent) {
                return [
                    'id' => $accent->id,
                    'language' => $accent->title,
                    'flag' => $accent->flag ? api::api.$accent->flag : null,

                ];
            });

        }


        return api_response([
            'languages' => $languages->map(function ($language) {
                return [
                    'id' => $language->id,
                    'language' => $language->title,
                    'flag' => $language->flag ? api::api.$language->flag : null,

                ];
            }),
            'student_languages'=> $s ?? null,

        ]);
    }

    public function show()
    {
        $student_id = auth()->user()->student->id;
        $id = Auth::id();
        $student = Student::where('id', $student_id)->find($student_id);
        if ($student->profile) {
            $prof = api::api . $student->profile;
        }
        $a = $this->getPendingSurveys($student_id);
        return api_response([
            'id' => $student->id,
            'profile' => $prof ?? null,
            'level' => $student->level,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'user_id' => $id,
            'student_id' => $student_id,
            'q'=> $a ?? null,
        ]);
    }
    public function getPendingSurveys($stu)
    {

        $result = [];

        $privates = PrivetClassReservation::where('student_id', $stu)
            ->where('status', 'completed')
            ->doesntHave('surveyResponse')
            ->get(['id']);

        foreach ($privates as $item) {
            $result[] = [
                'type' => 'private',
                'id' => $item->id,
            ];
        }

        $groups = GroupClassReservation::where('student_id', $stu)
            ->where('status', 'completed')
            ->doesntHave('surveyResponse')
            ->get(['id']);

        foreach ($groups as $item) {
            $result[] = [
                'type' => 'group',
                'id' => $item->id,
            ];
        }

        // ✅ ورکشاپ
        $workshops = WorkShopReservation::where('student_id', $stu)
            ->where('status', 'confirmed')
            ->doesntHave('surveyResponse')
            ->get(['id']);

        foreach ($workshops as $item) {
            $result[] = [
                'type' => 'workshop',
                'id' => $item->id,
            ];
        }

        // ✅ وبینار
        $webinars = WebinarReservation::where('student_id', $stu)
            ->where('status', 'confirmed')
            ->doesntHave('surveyResponse')
            ->get(['id']);

        foreach ($webinars as $item) {
            $result[] = [
                'type' => 'webinar',
                'id' => $item->id,
            ];
        }

        return $result;
    }


    public function index()
    {
        $student_id = auth()->user()->student->id;
        $student = Student::with('addresses')->where('id', $student_id)->first();

        $birthDate = Carbon::parse($student->birth_of_date);
        $age = $birthDate->diffInYears(Carbon::now());
        if ($age < 18) {
            $s = [
                'id' => $student->id,
                'profile' => $student->profile,
                'level' => $student->level ?? null,
                'identification_code' => $student->student_code,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'user_name' => $student->user_name,
                'age' => $student->age,
                'gender_id' => $student->gender_id ?? null,
                'national_code' => $student->national_code,
                'parents_national_code' => $student->parents_national_code,
                'place_of_birth' => $student->place_of_birth,
                'date_of_birth' => $student->date_of_birth,
                'Landline' => $student->Landline,
                'mobile' => $student->mobile,
                'email' => $student->email,
                'parents_mobile' => $student->parents_mobile,
                'parents_email' => $student->parents_email,
                'zoom_user_name' => $student->zoom_user_name,
                'meet_user_name' => $student->meet_user_name,
                'skype_user_name' => $student->skype_user_name,
                'voov_user_name' => $student->voov_user_name,
                'city_id' => $student->addresses->city_id ?? null,
                'country_id' => $student->addresses->country_id ?? null,
                'unit' => $student->addresses->unit ?? null,
                'postal_code' => $student->addresses->postal_code ?? null,
                'plate' => $student->addresses->plate ?? null,
                'district' => $student->addresses->district ?? null,
                'address' => $student->addresses->address ?? null,
                'longitude' => $student->addresses->longitude ?? null,
                'latitude' => $student->addresses->latitude ?? null,
                'is_above_18' => false,

            ];
        } else {
            $s = [
                'id' => $student->id,
                'profile' => $student->profile,
                'level' => $student->level ?? null,
                'identification_code' => $student->identification_code,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'user_name' => $student->user_name,
                'age' => $student->age,
                'gender_id' => $student->gender_id ?? null,
                'national_code' => $student->national_code,
                'place_of_birth' => $student->place_of_birth,
                'date_of_birth' => $student->date_of_birth,
                'Landline' => $student->Landline,
                'work_Landline' => $student->work_Landline,
                'mobile' => $student->mobile,
                'email' => $student->email,
                'zoom_user_name' => $student->zoom_user_name,
                'meet_user_name' => $student->meet_user_name,
                'skype_user_name' => $student->skype_user_name,
                'voov_user_name' => $student->voov_user_name,
                'city_id' => $student->addresses->city_id ?? null,
                'country_id' => $student->addresses->country_id ?? null,
                'unit' => $student->addresses->unit ?? null,
                'postal_code' => $student->addresses->postal_code ?? null,
                'plate' => $student->addresses->plate ?? null,
                'district' => $student->addresses->district ?? null,
                'address' => $student->addresses->address ?? null,
                'longitude' => $student->addresses->longitude ?? null,
                'latitude' => $student->addresses->latitude ?? null,
                'is_above_18' => true,

            ];

        }


        return api_response($s);
    }

    public function store(Request $request)
    {
        $student_id = auth()->user()->student->id;
        $validatedStudentData = $request->validate([
            'level' => 'nullable|string',
            'first_name' => 'nullable|string',
            'last_name' => 'nullable|string',
            'user_name' => 'nullable|string',
            'age' => 'nullable|integer|min:1',
            'gender_id' => 'nullable|integer',
            'national_code' => 'nullable|string',
            'place_of_birth' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'Landline' => 'nullable|string',
            'work_Landline' => 'nullable|string',
            'mobile' => 'nullable|string',
            'email' => 'nullable|email',
            'zoom_user_name' => 'nullable|string',
            'meet_user_name' => 'nullable|string',
            'skype_user_name' => 'nullable|string',
            'voov_user_name' => 'nullable|string',
        ]);
        if ($request->filled('date_of_birth')) {
            $validatedStudentData['date_of_birth'] = \Carbon\Carbon::parse($request->date_of_birth)->format('Y-m-d');
        }

        // اعتبارسنجی داده‌ها برای آدرس
        $validatedAddressData = $request->validate([
            'city_id' => 'nullable|integer',
            'country_id' => 'nullable|integer',
            'unit' => 'nullable|string',
            'postal_code' => 'nullable|string',
            'plate' => 'nullable|string',
            'district' => 'nullable|string',
            'address' => 'nullable|string',
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
        ]);
        // ایجاد یا بروزرسانی دانش‌آموز
        $student = Student::updateOrCreate(
            ['id' => $student_id], // استفاده از mobile به عنوان شناسه یکتا
            $validatedStudentData // داده‌های دانش‌آموز
        );

        // ایجاد یا بروزرسانی آدرس برای این دانش‌آموز
        $address = StudentAddress::updateOrCreate(
            ['student_id' => $student_id], // بررسی اینکه آدرس این دانش‌آموز موجود است
            $validatedAddressData // داده‌های آدرس
        );

        // بازگشت به کلاینت
        return api_response([

        ], 'با موفقیت ویرایش شد', 200);
    }

    public function suggestion()
    {
        $student_id = auth()->user()->student->id;
        $student = Student::where('id', $student_id)->first();
        $level = $this->getLevel($student_id);
        $classes = GroupClass::where('level_id', $level)->take(3)->get();
        $suggestions_group = $classes->map(function ($class) {
            return [
                'image' => $class->image,
                'title' => $class->subject_id,
                'language' => $class->language->title,
                'name' => $class->professor->first_name . ' ' . $class->professor->last_name,
                'schedules' => $class->days . ' ' . \Carbon\Carbon::parse($class->schedules->first()->end_time)->format('H:i') . ' ' . \Carbon\Carbon::parse($class->schedules->first()->start_time)->format('H:i'),
                'start_date' => $class->start_date,
                'end_date' => $class->end_date,
                'total_price' => $class->total_price,
                'id' => $class->id,
            ];
        });

        $professor = Professor::with('languages')->take(3)->get();
        $suggestions_professor = $professor->map(function ($professor) {
            return [
                'profile' => $professor->profile ? api::api . $professor->profile:null,
                'name' => $professor->first_name . ' ' . $professor->last_name,
                'languages' => $professor->languages->first()->title ?? 'اینگلیسی',
                'price' => $professor->price,
                'views_count' => $professor->views_count,
                'rate' => 3,

            ];
        });

        return api_response([
            'group_classes' => $suggestions_group,
            'professors' => $suggestions_professor,
        ], 'با موفقیت ارسال شد', 200);
    }

    private function getLevel($userId)
    {
        $levels = [
            'pre A1' => 4,
            'A1' => 1,
            'A2' => 1,
            'B1' => 2,
            'B2' => 2,
            'C1' => 3,
            'C2' => 3,

        ];

        $user = Student::find($userId);

        if (!$user || !isset($levels[$user->level])) {
            return null; // اگر کاربر یافت نشد یا مقدار سطح نامعتبر بود
        }

        return $levels[$user->level];
    }

    public function favorites()

    {

        try {

            $classes = [Webinar::class , WorkShop::class , Exam::class , BookLearning::class];

            foreach ($classes as $class) {

                $className = class_basename($class);

                $classLikes= Like::where('likeable_type',$class)->where('user_id',auth()->id())->get();

                ${$className . 'Collect'} = collect();

                foreach ($classLikes as $Like) {
                    ${$className . 'Collect'}->push($Like->likeable);
                }

                switch ($class) {

                    case Webinar::class :
                        ${$className . 'Mapped'} = ${$className . 'Collect'}->map(function ($item) {
                            return [
                                'id' => $item->id,
                            ];
                        });
                        break;
                    case WorkShop::class :
                        ${$className . 'Mapped'} = ${$className . 'Collect'}->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'date' => $item->date,
                            ];
                        });
                        break;
                    case Exam::class :
                        ${$className . 'Mapped'} = ${$className . 'Collect'}->map(function ($item) {
                            return [
                                'name'             => $item->name,
                                'description'      => $item->description,
                                'duration_minutes' => $item->duration_minutes,
                            ];
                        });
                        break;
                    case  BookLearning::class :

                        ${$className . 'Mapped'} = ${$className . 'Collect'}->map(function ($item) {
                            return [
                                'id'=> $item->id
                            ];
                        });
                }

            }

            return api_response([
                'Webinars' => $WebinarMapped,
                'WorkShops' => $WorkShopMapped,
                'Exams' => $ExamMapped,
                'BookLearning' => $BookLearningMapped,
            ]);

        }catch (\Exception $exception){
            return api_response([], $exception->getMessage(),500);
        }


    }

    public function group_classes()
    {
        $id = 30;
        $class = GroupClassReservation::with('groupClass')->where('student_id',$id)->get();
        $return = $class->map(function ($class) {
            return [
                'id'=>$class->groupClass->id,
                'image'=>$class->groupClass->image ? api::api . $class->groupClass->image:null,
                'title'=>$class->groupClass->subject->title,
                'professor'=>$class->groupClass->professor->full_name(),
                'language'=>$class->groupClass->language->title,
                'start'=>$class->groupClass->start_date,
                'end'=>$class->groupClass->end_date,
                'teachingType'=>$class->groupClass->teachingType->title,
                'link'=>$class->groupClass->class_link,
                'time'=>$class->groupClass->schedules()->first()->date,
            ];
        });

        return api_response($return , 'ok');
    }



}
