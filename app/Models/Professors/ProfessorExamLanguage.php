<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorExamLanguage extends Model
{
    protected $table = 'professor_exam_language';

    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class, 'language_id'); // ارتباط با جدول Languages
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }
    public function entrance_exam()
    {
        return $this->belongsTo(ExamLanguage::class, 'entrance_exam_language_id');
    }

}
