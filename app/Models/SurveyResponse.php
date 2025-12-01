<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    protected $fillable = [
        'student_id',
        'surveyable_id',
        'surveyable_type',
        'submitted_at',
    ];

    protected $dates = [
        'submitted_at',
    ];

    // رابطه polymorphic به رزرو مربوطه (کلاس، وبینار، ورکشاپ و ...)
    public function surveyable(): MorphTo
    {
        return $this->morphTo();
    }

    // رابطه به دانش‌آموز (کاربر)
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // آیتم‌های پاسخ (هر سوال با امتیاز)
    public function items(): HasMany
    {
        return $this->hasMany(SurveyResponseItem::class);
    }
}
