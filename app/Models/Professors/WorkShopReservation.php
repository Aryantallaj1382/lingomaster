<?php

namespace App\Models\Professors;

use App\Models\payment\Payment;
use App\Models\Student\Student;
use App\Models\SurveyResponse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class WorkShopReservation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function WorkShop(): BelongsTo
    {
        return $this->belongsTo(WorkShop::class);
    }
    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }
    public function surveyResponse(): MorphOne
    {
        return $this->morphOne(SurveyResponse::class, 'surveyable');
    }
}
