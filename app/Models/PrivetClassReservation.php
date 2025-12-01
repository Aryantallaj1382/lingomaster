<?php
namespace App\Models;

use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningSubgoal;
use App\Models\Professors\Offline;
use App\Models\Professors\PrivetClassBook;
use App\Models\Professors\TeacherTimeSlot;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivetClassReservation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function teachingType()
    {
        return $this->belongsTo(TeachingType::class);
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
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
    public function cancellationReason(): \Illuminate\Database\Eloquent\Relations\HasOne|PrivetClassReservation
    {
        return $this->hasOne(PrivateClassDeletionReason::class, 'privet_id');
    }


}
