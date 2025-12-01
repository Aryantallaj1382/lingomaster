<?php

namespace App\Http\Controllers\Api\Students;

use App\Http\Controllers\Controller;
use App\Models\contacts\api;
use App\Models\Professors\PrivetClassReservation;
use Illuminate\Http\Request;

class PrivateClass extends Controller
{
    public function index(Request $request)
    {
        $type = $request->input('type');
        $id = auth()->user()->student->id;
        $private = PrivetClassReservation::where('student_id', $id)->where('status',$type)->take(3)->get();
        $private1 =$private->map(function ($item) {
            return[
                'image'=>$item->professor->profile ?api::api.$item->professor->profile : null,
                'professor_id'=>$item->professor->id ?? null,
                'id'=>$item->id ?? null,
                'language'=>$item->language->title ?? null,
                'name'=>$item->professor->full_name() ?? null,
                'time'=>$item->teacherTimeSlots->first()->time ?? null,
                'date'=>$item->teacherTimeSlots->first()->date ?? null,
                'platform'=>$item->platform ?? null,
                'address'=>$item->address->title ?? null,
                'teachingType'=>$item->teachingType->title ?? null,
                'teachingTypeId'=>$item->teachingType->id ?? null,
                'class_link'=>$item->class_link ?? null,
            ];
        });

        return api_response($private1,'ok');

    }

}
