<?php

namespace App\Models\Professors;

use App\Models\Financial\Payment;
use App\Models\Student\Student;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class GroupClassReservation extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function surveyResponse(): MorphOne
    {
        return $this->morphOne(SurveyResponse::class, 'surveyable');
    }
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    public function groupClass(): BelongsTo
    {
        return $this->belongsTo(GroupClass::class);
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }
}
