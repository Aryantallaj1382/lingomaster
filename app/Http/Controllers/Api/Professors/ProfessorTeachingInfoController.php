<?php

namespace App\Http\Controllers\Api\Professors;

use App\Models\contacts\api;
use App\Models\GeographicRegion;
use Illuminate\Routing\Controller;  // حتماً از این استفاده کن
use App\Models\Languages;
use App\Models\Professors\Offline;
use App\Models\Professors\Online;
use Illuminate\Http\Request;

class ProfessorTeachingInfoController extends Controller
{

    public function  showProfessorDetails(Request $request)
    {
        $professor = auth()->user()->professor;


        $languages = $professor->languages;

        $languagesDetails = $languages->map(function ($language) use ($professor) {
            return [
                'language_id' => $language->id,
                'language' => $language->title,
                'flag' => api::api.$language->flag,
                'genders' => $professor->genders->where('pivot.language_id', $language->id)->pluck('title')->toArray(),
                'online_classes' => $professor->online
                    ? $professor->online->where('language_id', $language->id)
                        ->pluck('platform_name')->toArray()
                    : [],
                'offline_classes' => $professor->Offline instanceof \Illuminate\Database\Eloquent\Collection
                    ? $professor->offline
                        ->where('language_id', $language->id)
                        ->pluck('type')->toArray()
                    : [],

                'age_groups_with_levels' => $professor->ageGroupTitles(),
                'level_1' => $professor->ageGroupLanguageLevels
                    ->where('age_group_id', 1)
                    ->map(function ($ageGroupLanguageLevel) {
                        return $ageGroupLanguageLevel->languageLevel->title;
                    })->values()->toArray(),

                'level_2' => $professor->ageGroupLanguageLevels
                    ->where('age_group_id', 2)
                    ->map(function ($ageGroupLanguageLevel) {
                        return $ageGroupLanguageLevel->languageLevel->title;
                    })
                    ->values()  // این خط کلیدهای آرایه را بازنشانی می‌کند
                    ->toArray(), // تبدیل به آرایه



                'level_3' => $professor->ageGroupLanguageLevels
                    ->where('age_group_id', 3)
                    ->map(function ($ageGroupLanguageLevel) {
                        return $ageGroupLanguageLevel->languageLevel->title; // دریافت عنوان سطح زبان
                    })
                    ->values()
                    ->toArray(),




            ];
        });


        $info = $languages->map(function ($language) use ($professor) {
            return [
                'about_text' => $professor->aboutTexts->where('language_id', $language->id)->first()->about_text ?? null,
                'language' => $language->title,
                'language_id' => $language->id,

                'genders' => $professor->genders()
                    ->wherePivot('language_id', $language->id)
                    ->pluck('genders.id') // مشخص کردن اینکه 'id' مربوط به جدول 'genders' است
                    ->toArray(),

                'online_classes' => $professor->online
                    ->where('language_id', $language->id)
                    ->values()
                    ->all(),
                'offline_classes' => $professor->offline
                    ->where('language_id', $language->id)
                    ->values()
                    ->all(),


                'nearest_classes_in_person' => $professor->nearestClasses
                    ->where('type', 'in_person')
                    ->where('language_id', $language->id)
                    ->pluck('title')
                    ->join(', '),

                'nearest_classes_online' => $professor->nearestClasses
                    ->where('type', 'online')
                    ->where('language_id', $language->id)
                    ->pluck('title')
                    ->join(', '),

                'age_groups_with_levels' => $professor->ageGroups()
                    ->wherePivot('language_id', $language->id)
                    ->get()
                    ->map(function ($ageGroup) use ($professor, $language) {
                        return [
                            'id' => $ageGroup->id,
                            'language_levels' => $professor->ageGroupLanguageLevels
                                ->where('age_group_id', $ageGroup->id)
                                ->where('language_id', $language->id)
                                ->pluck('language_level_id')
                                ->values(),
                        ];
                    })->toArray(),



            ];
        });

        return api_response([
            'languages' => $languagesDetails,
            'info' => $info,
        ]);
    }

    public function storeProfessorDetails(Request $request)
    {
        $data = $request->validate([
            'language_id' => 'required|exists:languages,id',
            'about_text' => 'nullable|string|max:5000',
            'age_groups' => 'nullable|array',
            'age_groups.*' => 'exists:age_groups,id',
            'genders' => 'nullable|array',
            'genders.*' => 'exists:genders,id',
            'language_levels' => 'nullable|array',
            'language_levels.*' => 'exists:language_levels,id',
            'nearest_classes_online' => 'nullable',
            'nearest_classes_in_person' => 'nullable',
            'online_classes' => 'nullable|array',
            'online_classes.*.platform_name' => 'required|string|max:255',
            'online_classes.*.platform_identifier' => 'required|string|max:255',
            'offline_classes' => 'nullable|array',
            'offline_classes.*.geo' => 'nullable|array',
            'offline_classes.*.geo.*' => 'exists:geographic_regions,id',
            'offline_classes.*.region_ids' => 'nullable|array',
            'offline_classes.*.region_ids.*' =>'nullable',
            'offline_classes.*.type' => 'nullable|string|max:255',
            'offline_classes.*.title' => 'nullable|string|max:255',
            'offline_classes.*.latitude' => 'nullable|numeric',
            'offline_classes.*.longitude' => 'nullable|numeric',
            'offline_classes.*.city_id' => 'nullable|exists:iran_cities,id',
            'offline_classes.*.video' => 'nullable',
            'offline_classes.*.country_id' => 'nullable|exists:countries,id',
            'offline_classes.*.address' => 'nullable|string|max:255',
            'offline_classes.*.postal_code' => 'nullable|string|max:10',
            'offline_classes.*.unit' => 'nullable|string|max:10',
            'offline_classes.*.plate' => 'nullable|string|max:20',
            'age_groups_with_levels' => 'nullable|array',
            'age_groups_with_levels.*.id' => 'required|exists:age_groups,id',
            'age_groups_with_levels.*.language_levels' => 'nullable|array',
            'age_groups_with_levels.*.language_levels.*' => 'exists:language_levels,id',
        ], [], [
            'language_id' => 'شناسه زبان',
            'about_text' => 'متن توضیحات',
            'age_groups' => 'گروه‌های سنی',
            'age_groups.*' => 'گروه سنی',
            'genders' => 'جنسیت‌ها',
            'genders.*' => 'جنسیت',
            'language_levels' => 'سطوح زبان',
            'language_levels.*' => 'سطح زبان',
            'nearest_classes_online' => 'کلاس‌های آنلاین نزدیک',
            'nearest_classes_in_person' => 'کلاس‌های حضوری نزدیک',
            'online_classes' => 'کلاس‌های آنلاین',
            'online_classes.*.platform_name' => 'نام پلتفرم آنلاین',
            'online_classes.*.platform_identifier' => 'شناسه پلتفرم آنلاین',
            'offline_classes' => 'کلاس‌های حضوری',
            'offline_classes.*.type' => 'نوع کلاس حضوری',
            'offline_classes.*.title' => 'عنوان کلاس حضوری',
            'offline_classes.*.latitude' => 'عرض جغرافیایی کلاس حضوری',
            'offline_classes.*.longitude' => 'طول جغرافیایی کلاس حضوری',
            'offline_classes.*.city_id' => 'شناسه شهر کلاس حضوری',
            'offline_classes.*.video' => 'ویدئوی کلاس حضوری',
            'offline_classes.*.country_id' => 'شناسه کشور کلاس حضوری',
            'offline_classes.*.address' => 'آدرس کلاس حضوری',
            'offline_classes.*.region' => 'منطقه کلاس حضوری',
            'offline_classes.*.postal_code' => 'کد پستی کلاس حضوری',
            'offline_classes.*.unit' => 'واحد کلاس حضوری',
            'offline_classes.*.plate' => 'پلاک کلاس حضوری',
            'age_groups_with_levels' => 'گروه‌های سنی با سطوح',
            'age_groups_with_levels.*.id' => 'شناسه گروه سنی با سطح',
            'age_groups_with_levels.*.language_levels' => 'سطوح زبان گروه سنی با سطح',
            'age_groups_with_levels.*.language_levels.*' => 'سطح زبان گروه سنی با سطح',
        ]);


        $professor = auth()->user()->professor;


        $languageId = $data['language_id'];

        $this->handleAgeGroups($professor, $languageId, $data['age_groups'] ?? []);
        $this->handleGenders($professor, $languageId, $data['genders'] ?? []);
        $this->handleLanguageLevels($professor, $languageId, $data['language_levels'] ?? []);
        $this->handleNearestClasses($professor, $languageId, $data['nearest_classes_online'] ?? null, $data['nearest_classes_in_person'] ?? null);
        $this->handleAboutText($professor, $languageId, $data['about_text'] ?? null);
        $this->handleOnlineClasses($professor, $languageId, $data['online_classes'] ?? []);
        $this->handleOfflineClasses($professor, $languageId, $data['offline_classes'] ?? []);
        $this->handleAgeGroupLanguageLevels($professor, $languageId, $data['age_groups_with_levels'] ?? []);

        return api_response($professor, 'با موفقیت ذخیره شد');
    }

    private function handleAgeGroups($professor, $languageId, $ageGroups)
    {
        if (!empty($ageGroups)) {
            $professor->ageGroups()
                ->wherePivot('language_id', $languageId)
                ->detach();

            $syncData = [];
            foreach ($ageGroups as $groupId) {
                $syncData[$groupId] = ['language_id' => $languageId];
            }

            $professor->ageGroups()->attach($syncData);
        }
    }



    private function handleGenders($professor, $languageId, $genders)
    {
        if (!empty($genders)) {
            $professor->genders()
                ->wherePivot('language_id', $languageId)
                ->syncWithPivotValues($genders, ['language_id' => $languageId]);
        }
    }

    private function handleLanguageLevels($professor, $languageId, $languageLevels)
    {
        if (!empty($languageLevels)) {
            $professor->languageLevels()
                ->wherePivot('language_id', $languageId)
                ->detach();

            // حالا ثبت سطح‌های جدید با language_id
            $data = [];
            foreach ($languageLevels as $levelId) {
                $data[$levelId] = ['language_id' => $languageId];
            }

            $professor->languageLevels()->attach($data);
        }
    }


    private function handleNearestClasses($professor, $languageId, $nearestClassesOnline, $nearestClassesInPerson)
    {
        if ($nearestClassesOnline !== null) {
            $professor->nearestClasses()->updateOrCreate(
                ['language_id' => $languageId, 'type' => 'online'],
                ['title' => $nearestClassesOnline]
            );
        }

        if ($nearestClassesInPerson !== null) {
            $professor->nearestClasses()->updateOrCreate(
                ['language_id' => $languageId, 'type' => 'in_person'],
                ['title' => $nearestClassesInPerson]
            );
        }
    }

    private function handleAboutText($professor, $languageId, $aboutText)
    {
        if (!empty($aboutText)) {
            $professor->aboutTexts()->updateOrCreate(
                ['language_id' => $languageId],
                ['about_text' => $aboutText]
            );
        }
    }

    private function handleOnlineClasses($professor, $languageId, $onlineClasses)
    {
        Online::where('professor_id', $professor->id)->where('language_id', $languageId)->delete();
        foreach ($onlineClasses as $onlineClass) {
            $professor->online()->updateOrCreate(
                ['language_id' => $languageId, 'platform_name' => $onlineClass['platform_name']],
                ['platform_identifier' => $onlineClass['platform_identifier']]
            );
        }
    }

    private function handleOfflineClasses($professor, $languageId, $offlineClasses)
    {
        Offline::where('professor_id', $professor->id)
            ->where('language_id', $languageId)
            ->delete();

        foreach ($offlineClasses as $offlineClass) {
            $videoPath = null;

            if (
                isset($offlineClass['video']) &&
                $offlineClass['video'] instanceof \Illuminate\Http\UploadedFile &&
                $offlineClass['video']->isValid()
            ) {
                $videoPath = $offlineClass['video']->store('videos/professor_home', 'public');
            }

            // همیشه کلاس ایجاد میشه
            $newOfflineClass = $professor->Offline()->create([
                'language_id' => $languageId,
                'type' => $offlineClass['type'],
                'title' => $offlineClass['title'] ?? null,
                'latitude' => $offlineClass['latitude'] ?? null,
                'longitude' => $offlineClass['longitude'] ?? null,
                'city_id' => $offlineClass['city_id'] ?? null,
                'country_id' => $offlineClass['country_id'] ?? null,
                'address' => $offlineClass['address'] ?? null,
                'region' => $offlineClass['region'] ?? null,
                'postal_code' => $offlineClass['postal_code'] ?? null,
                'unit' => $offlineClass['unit'] ?? null,
                'plate' => $offlineClass['plate'] ?? null,
                'areas' => isset($offlineClass['areas']) ? json_encode($offlineClass['areas']) : null,
                'video' => $videoPath,
            ]);

            // فقط برای type خاص: مناطق جغرافیایی و متنی
            if ($offlineClass['type'] === 'inPersonAtStudentPlace') {

                // مناطق جغرافیایی
                $newOfflineClass->geographicRegions()->sync($offlineClass['geo'] ?? []);

                // مناطق متنی
                if (!empty($offlineClass['region_ids']) && is_array($offlineClass['region_ids'])) {
                    foreach ($offlineClass['region_ids'] as $regionName) {
                        if ($regionName && is_string($regionName)) {
                            $newOfflineClass->regions()->create([
                                'region' => $regionName,
                            ]);
                        }
                    }
                }
            }
        }
    }



    private function handleAgeGroupLanguageLevels($professor, $languageId, $ageGroupsWithLevels)
    {
        // حذف اطلاعات قبلی
        $professor->ageGroupLanguageLevels()->where('language_id', $languageId)->delete();
        $professor->ageGroups()->wherePivot('language_id', $languageId)->detach();

        $ageGroupsData = [];
        $ageGroupLanguageLevelsData = [];

        foreach ($ageGroupsWithLevels as $ageGroup) {
            foreach ($ageGroup['language_levels'] as $languageLevelId) {
                $ageGroupLanguageLevelsData[] = [
                    'professor_id' => $professor->id,
                    'language_id' => $languageId,
                    'age_group_id' => $ageGroup['id'],
                    'language_level_id' => $languageLevelId,
                ];
            }

            $ageGroupsData[$ageGroup['id']] = ['language_id' => $languageId];
        }

        if (!empty($ageGroupLanguageLevelsData)) {
            \DB::table('age_group_language_levels')->insert($ageGroupLanguageLevelsData);
        }

        // تغییر از sync به attach برای کنترل بیشتر
        foreach ($ageGroupsData as $ageGroupId => $pivotData) {
            $professor->ageGroups()->attach($ageGroupId, $pivotData);
        }
    }
    public function region()
    {
        $r  = GeographicRegion::all();
        return api_response($r);
    }
}
