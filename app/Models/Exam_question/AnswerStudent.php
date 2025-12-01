<?php
namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Model;

class AnswerStudent extends Model
{

    protected $table = 'answer_students';

    protected $fillable = [
        'exam_student_id', 'question_id', 'answer_text',
        'media_path', 'option_id','variant_id', 'is_correct', 'review_status' 
    ];



    public function examStudent()
    {
        return $this->belongsTo(ExamStudent::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

//    public function option()
//    {
//        return $this->belongsTo(QuestionOption::class, 'option_id');
//    }
}
