<?php

namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantOption extends Model
{
    use HasFactory;
    protected $table = 'options';

    public function variant()
    {
        return $this->belongsTo(QuestionVariant::class, 'question_variant_id');
    }


}
