<?php
namespace App\Models\Exam_question;

use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Model;

class ExamStudent extends Model
{

    protected $table = 'exam_students';

    protected $fillable = [
        'student_id', 'exam_id', 'status',
        'payment_status' ,        'score', 'attempt_number', 'started_at',
        'finished_at', 'feedback'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function answers()
    {
        return $this->hasMany(AnswerStudent::class);
    }
}
