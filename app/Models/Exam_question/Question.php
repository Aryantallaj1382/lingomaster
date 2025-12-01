<?php
namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'exam_id', 'skill_id', 'question_type_id', 'title',
        'question_text', 'difficulty', 'parent'
    ];

    public function exam()
    {
        return $this->belongsToMany(Exam::class ,'exam_questions')->withPivot('order');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id');
    }

    public function questionType()
    {
        return $this->belongsTo(QuestionType::class);
    }

    public function medias()
    {
        return $this->hasMany(MediaQuestion::class);
    }

    public function children()
    {
        return $this->hasMany(Question::class, 'parent');
    }

    public function parentQuestion()
    {
        return $this->belongsTo(Question::class, 'parent');
    }

    public function variants()
    {
        return $this->hasMany(QuestionVariant::class);
    }

    public function blanks(){
        return $this->hasMany(QuestionBlank::class);
    }

}
