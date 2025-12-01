<?php
namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Model;

class QuestionType extends Model
{

    protected $table = 'question_types';

    protected $fillable = ['name'];

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}
