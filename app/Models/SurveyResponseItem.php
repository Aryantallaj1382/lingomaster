<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponseItem extends Model
{
    protected $fillable = [
        'survey_response_id',
        'survey_question_id',
        'rating',
    ];

    // رابطه به پاسخ اصلی
    public function surveyResponse(): BelongsTo
    {
        return $this->belongsTo(SurveyResponse::class);
    }

    // رابطه به سوال نظرسنجی
    public function surveyQuestion(): BelongsTo
    {
        return $this->belongsTo(SurveyQuestion::class);
    }
}
