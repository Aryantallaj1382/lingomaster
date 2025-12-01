<?php

namespace App\Http\Controllers\Api\students;

use App\Http\Controllers\Controller;
use App\Models\Student\Student;

class PanelController extends Controller
{




    public function index()
    {
        $student_id = auth()->user()->student->id;
        $student= Student::where('id',$student_id)->first();
        $s = [
            'id' => $student->id,
            'profile' => $student->profile,
            'level' => $student->level,
            'identification_code' => $student->identification_code,
            'first_name' => $student->first_name,
            'first_name' => $student->last_name,
            'first_name' => $student->user_name,
            'first_name' => $student->age,
            'first_name' => $student->gender_id,
            'first_name' => $student->first_name,
            'first_name' => $student->first_name,
            'first_name' => $student->first_name,
            'first_name' => $student->first_name,
            'first_name' => $student->first_name,
            'first_name' => $student->first_name,
            'first_name' => $student->first_name,
            'first_name' => $student->first_name,
        ];




        return api_response($s);
    }
}
