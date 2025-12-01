<?php

namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionBlank extends Model
{
    use HasFactory;
    protected $table = 'question_blanks';
    
    public function options()
    {
        return $this->hasMany(Option::class);
    }
    
}
