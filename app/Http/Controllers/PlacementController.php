<?php

namespace App\Http\Controllers;

use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\Offline;
use App\Models\Professors\Online;
use App\Models\Professors\Professor;
use App\Models\Professors\Skill;
use App\Models\TeachingType;
use Illuminate\Http\Request;
use Monolog\Level;

class PlacementController extends Controller
{
    public function index(Request $request)
    {
        $professor_id = $request->input('professor_id');
        $lang = $request->input('language');


        $age = AgeGroup::where('id','!=',4)->get();
        $level = LanguageLevel::where('id','!=',4)->get();
        $skill = Skill::all();



        if ($professor_id) {
            $professor = Professor::find($professor_id);
            $hasOnlineClass = $professor->online()->where('language_id', $lang)->exists();
            $hasOfflineClass = $professor->offline()->where('language_id', $lang)->exists();
            $teachingTypes = [];

            if ($hasOnlineClass) {
                $teachingTypes[] = [
                    'id' => 2,
                    'title' => 'آنلاین',
                ];
            }

            if ($hasOfflineClass) {
                $teachingTypes[] = [
                    'id' => 1,
                    'title' => 'حضوری',
                ];
            }

            $platform = Online::where('professor_id', $professor->id)->where('language_id', $lang)->pluck('platform_name')->toArray();
            $address = Offline::where('professor_id', $professor->id)
                ->where('language_id', $lang)
                ->select(['type', 'video', 'latitude', 'longitude', 'id'])
                ->get()
                ->map(function ($offline) {
                    return [
                        'id' => $offline->id,
                        'type' => $offline->type,
                        'video' => $offline->video ? api::api . $offline->video : null,
                        'latitude' => $offline->latitude,
                        'longitude' => $offline->longitude,
                    ];
                });

        }


        return api_response([
            'age' => $age,
            'level' => $level,
            'skill' => $skill,
            'teachingTypes' => $teachingTypes ?? null,
            'platform' => $platform ?? null,
            'address' => $address ?? null,

        ]);
    }
    public function professors(Request $request)
    {
        $lang = $request->input('language');
        $age = $request->input('ageGroup');
        $level = $request->input('level');
        $skill = $request->input('skill_id');

        $professors = Professor::query();

        if ($age || $lang || $level) {
            $professors->whereHas('ageGroupLanguageLevels', function($q) use ($age, $lang, $level) {
                if ($age) {
                    $q->where('age_group_id', $age);
                }
                if ($lang) {
                    $q->where('language_id', $lang);
                }
                if ($level) {
                    $q->where('language_level_id', $level);
                }
            });
        }

        $professor = $professors->paginate(10);
        $professor->getCollection()->transform(function($item){
            return [
                'name'=> $item->full_name(),
                'id'=> $item->id,
                'profile' => $item->profile ?api::api.$item->profile :null,
            ];
        });





        return api_response($professor);

    }
}
