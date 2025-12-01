<?php

namespace App\Http\Controllers\Api\Professors;

use App\Http\Controllers\Controller;
use App\Models\GeographicRegion;
use App\Models\Languages;
use App\Models\Professors\Offline;
use Illuminate\Http\Request;

class TeachingInfo extends Controller
{

    public function  showProfessorDetails(Request $request)
    {
        $professor = auth()->user()->professor;

        $languages = Languages::all();

        $languagesDetails = $languages->map(function ($language) use ($professor) {
            return [
                'language_id' => $language->id,
                'language' => $language->title,
                'flag' => $language->flag,
                'about_text' => $professor->aboutTexts->where('language_id', $language->id)->first()->about_text ?? null,
                'age_groups' => $professor->ageGroups->where('pivot.language_id', $language->id)->pluck('id')->toArray(),
                'genders' => $professor->genders->where('pivot.language_id', $language->id)->pluck('id')->toArray(),
                'language_levels' => $professor->languageLevels->where('pivot.language_id', $language->id)->pluck('id')->toArray(),
                'nearest_classes_online' => $professor->nearestClasses
                    ->where('language_id', $language->id)
                    ->where('type', 'online')
                    ->count(),
                'nearest_classes_in_person' => $professor->nearestClasses
                    ->where('language_id', $language->id)
                    ->where('type', 'in_person')
                    ->count(),
                'online_classes' => $professor->onlineClasses
                    ? $professor->onlineClasses->where('language_id', $language->id)
                        ->map(function ($class) {
                            return [
                                'platform_name' => $class->platform_name,
                                'platform_identifier' => $class->platform_identifier,
                            ];
                        })->toArray()
                    : [],
                'offline_classes' => $professor->offlineClasses instanceof \Illuminate\Database\Eloquent\Collection
                    ? $professor->offlineClasses
                        ->where('language_id', $language->id)
                        ->map(function ($class) {
                            return [
                                'type' => $class->type,
                                'latitude' => $class->latitude,
                                'longitude' => $class->longitude,
                                'city_id' => $class->city_id,
                                'country_id' => $class->country_id,
                                'address' => $class->address,
                                'region' => $class->region,
                                'postal_code' => $class->postal_code,
                                'unit' => $class->unit,
                                'plate' => $class->plate,
                            ];
                        })
                        ->toArray()
                    : [],

                'age_groups_with_levels' => $professor->ageGroups->where('pivot.language_id', $language->id)->map(function ($ageGroup) use ($language, $professor) {
                    return [
                        'id' => $ageGroup->id,
                        'language_levels' => $professor->languageLevels->where('pivot.language_id', $language->id)->pluck('id')->toArray(),
                    ];
                }),
            ];
        });

        $info = [
            'age_groups' => $professor->ageGroups->pluck('title')->toArray(),
            'genders' => $professor->genders->pluck('title')->toArray(),
            'teaching_types' => [
                'online' => $professor->online->all(),
                'in_person' => $professor->offline->all(),
            ],
            'nearest_classes_in_person' => $professor->nearestClasses
                ->where('type', 'in_person')
                ->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'title' => $class->title,
                    ];
                })
                ->toArray(),
            'nearest_classes_online' => $professor->nearestClasses
                ->where('type', 'online')
                ->map(function ($class) {
                    return [
                        'id' => $class->id,
                        'title' => $class->title,
                    ];
                })
                ->toArray(),
            'age_groups_with_levels' => $professor->ageGroups->map(function ($ageGroup) use ($professor) {
                return [
                    'id' => $ageGroup->id,
                    'language_levels' => $professor->languageLevels
                        ->where('pivot.age_group_id', $ageGroup->id)
                        ->pluck('id')
                        ->toArray(),
                ];
            }),
            'languages' => $languagesDetails,  // assuming the original mapping is still needed
        ];

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
            'nearest_classes_online' => 'nullable|integer|min:0',
            'nearest_classes_in_person' => 'nullable|integer|min:0',
            'online_classes' => 'nullable|array',
            'online_classes.*.platform_name' => 'required|string|max:255',
            'online_classes.*.platform_identifier' => 'required|string|max:255',
            'offline_classes' => 'nullable|array',
            'offline_classes.*.type' => 'required|string|max:255',
            'offline_classes.*.latitude' => 'required|numeric',
            'offline_classes.*.longitude' => 'required|numeric',
            'offline_classes.*.city_id' => 'required|exists:iran_cities,id',
            'offline_classes.*.country_id' => 'required|exists:countries,id',
            'offline_classes.*.address' => 'required|string|max:255',
            'offline_classes.*.region' => 'required|string|max:255',
            'offline_classes.*.postal_code' => 'nullable|string|max:10',
            'offline_classes.*.unit' => 'nullable|string|max:10',
            'offline_classes.*.plate' => 'nullable|string|max:20',
            'offline_classes.*.areas' => 'nullable|array',
            'age_groups_with_levels' => 'nullable|array',
            'age_groups_with_levels.*.id' => 'required|exists:age_groups,id',
            'age_groups_with_levels.*.language_levels' => 'nullable|array',
            'age_groups_with_levels.*.language_levels.*' => 'exists:language_levels,id',
        ]);

        $professor = auth()->user()->professor;

        if (!$professor) {
            return response()->json(['error' => 'User is not associated with a professor.'], 400);
        }

        $languageId = $data['language_id'];

        $this->handleAgeGroups($professor, $languageId, $data['age_groups'] ?? []);
        $this->handleGenders($professor, $languageId, $data['genders'] ?? []);
        $this->handleLanguageLevels($professor, $languageId, $data['language_levels'] ?? []);
        $this->handleNearestClasses($professor, $languageId, $data['nearest_classes_online'] ?? null, $data['nearest_classes_in_person'] ?? null);
        $this->handleAboutText($professor, $languageId, $data['about_text'] ?? null);
        $this->handleOnlineClasses($professor, $languageId, $data['online_classes'] ?? []);
        $this->handleOfflineClasses($professor, $languageId, $data['offline_classes'] ?? []);
        $this->handleAgeGroupLanguageLevels($professor, $languageId, $data['age_groups_with_levels'] ?? []);

        return api_response($professor, 'Professor details saved successfully!');
    }

    private function handleAgeGroups($professor, $languageId, $ageGroups)
    {
        if (!empty($ageGroups)) {
            $professor->ageGroups()
                ->wherePivot('language_id', $languageId)
                ->syncWithPivotValues($ageGroups, ['language_id' => $languageId]);
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
                ->syncWithPivotValues($languageLevels, ['language_id' => $languageId]);
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
        foreach ($onlineClasses as $onlineClass) {
            $professor->online()->updateOrCreate(
                ['language_id' => $languageId, 'platform_name' => $onlineClass['platform_name']],
                ['platform_identifier' => $onlineClass['platform_identifier']]
            );
        }
    }

    private function handleOfflineClasses($professor, $languageId, $offlineClasses)
    {
        foreach ($offlineClasses as $offlineClass) {
            $professor->Offline()->create([
                'language_id' => $languageId,
                'type' => $offlineClass['type'],
                'latitude' => $offlineClass['latitude'],
                'longitude' => $offlineClass['longitude'],
                'city_id' => $offlineClass['city_id'],
                'country_id' => $offlineClass['country_id'],
                'address' => $offlineClass['address'],
                'region' => $offlineClass['region'],
                'postal_code' => $offlineClass['postal_code'] ?? null,
                'unit' => $offlineClass['unit'] ?? null,
                'plate' => $offlineClass['plate'] ?? null,
                'areas' => isset($offlineClass['areas']) ? json_encode($offlineClass['areas']) : null,
            ]);
        }
    }


    private function handleAgeGroupLanguageLevels($professor, $languageId, $ageGroupsWithLevels)
    {
        foreach ($ageGroupsWithLevels as $ageGroup) {
            foreach ($ageGroup['language_levels'] as $languageLevelId) {
                $professor->ageGroupLanguageLevels()->updateOrCreate(
                    [
                        'professor_id'=>$professor->id,
                        'language_id' => $languageId,
                        'age_group_id' => $ageGroup['id'],
                        'language_level_id' => $languageLevelId,
                    ],
                    []
                );
            }
        }
    }

}
