<?php
namespace App\Models;

use App\Models\Professors\PrivetClassReservation;
use Illuminate\Database\Eloquent\Model;

class ReportRegistration extends Model
{
    protected $table = 'report_registration';

    protected $fillable = [
        'absence',
        'absence_time',
        'speaking', // در صورتی که نوع integer باشه
        'writing',
        'reading',
        'listening',
        'vocabulary',
        'final_score',
        'grammar',
        'student_status',
        'exam_solutions',
        'strengths',
        'weaknesses',
        'solutions',
        'score',
        'exam_part',
        'skills',
        'privet_teacher_time_slot'

    ];

    protected $casts = [
        'exam_solutions' => 'array',
        'strengths' => 'array',
        'weaknesses' => 'array',
        'solutions' => 'array',
        'score' => 'array',
        'exam_part' => 'array',
        'skills' => 'array',
    ];

    public function classReservation()
    {
        return $this->belongsTo(PrivetClassReservation::class, 'class_id');
    }

    public function books()
    {
        return $this->hasMany(ReportBook::class);
    }

    public function homeWorks()
    {
        return $this->hasMany(ReportHomeWork::class);
    }

    public function privateTeacherTimeSlot()
    {
        return $this->belongsTo(PrivateTeacherTimeSlot::class, 'privet_teacher_time_slot');
    }

}
