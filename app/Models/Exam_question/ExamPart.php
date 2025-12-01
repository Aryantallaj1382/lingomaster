<?php

namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamPart extends Model
{
    use HasFactory;

    protected $table = 'exam_parts';

    protected $fillable = [
        'exam_id','exam_parts', 'number', 'title', 'type', 'description', 'center_message',
        'duration', 'questionType', 'order', 'passenger_title', 'questions_title',
        'multiple_correct', 'passenger'
    ];

    public function questions()
    {
        return $this->hasMany(Question::class, 'exam_part_id');
    }

    public function medias()
    {
        return $this->hasMany(MediaQuestion::class);
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
