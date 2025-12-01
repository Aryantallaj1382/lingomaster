<?php

namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionVariant extends Model
{
    use HasFactory;

    protected $table = 'question_variants';

    protected $fillable = ['question_id', 'variant_id', 'variant_text'];

    public function question()
    {
        return $this->belongsTo(Question::class);

    }

    public function options()
    {
        return $this->hasMany(VariantOption::class);
    }
}
