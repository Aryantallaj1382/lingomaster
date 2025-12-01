<?php
namespace App\Models\Professors;

use App\Models\AgeGroup;
use App\Models\Financial\Payment;
use App\Models\Languages;
use App\Models\PrivateTeacherTimeSlot;
use App\Models\Student\Student;
use App\Models\SurveyResponse;
use App\Models\TeachingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class PrivetClassReservation extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'private_class_reservations';

    public function teachingType()
    {
        return $this->belongsTo(TeachingType::class);
    }

// App\Models\PrivateClassReservation.php

    public function skillRelation()
    {
        return $this->belongsTo(Skill::class, 'skill_id');
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
    public function surveyResponse(): MorphOne
    {
        return $this->morphOne(SurveyResponse::class, 'surveyable');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function Offline()
    {
        return $this->belongsTo(Offline::class);
    }

    public function ageGroup()
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function languageLevel()
    {
        return $this->belongsTo(LanguageLevel::class);
    }

    public function subject()
    {
        return $this->belongsTo(LearningSubgoal::class);
    }

    public function teacherTimeSlots()
    {
        return $this->belongsToMany(TeacherTimeSlot::class, 'privet_teacher_time_slot');
    }
    public function books()
    {
        return $this->hasMany(PrivetClassBook::class , 'privet_class_id');
    }
    public function cancellationReason(): HasOne|PrivetClassReservation
    {
        return $this->hasOne(PrivateClassDeletionReason::class, 'privet_id');
    }

    public function penalty(): HasOne|PrivetClassReservation
    {
        return $this->hasOne(PrivateClassPenalty::class, 'privet_id');
    }
    public function levelAssessments(): HasOne|PrivetClassReservation
    {
        return $this->hasOne(LevelAssessment::class,'private_class_reservation_id');
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function address()
    {
        return $this->belongsTo(Offline::class, 'address_id');
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function privateTeacherTimeSlots()
    {
        return $this->hasMany(PrivateTeacherTimeSlot::class, 'privet_class_reservation_id');
    }


}
