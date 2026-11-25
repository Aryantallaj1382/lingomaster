<?php

namespace App\Models\Student;

use App\Models\Languages;
use App\Models\Like;
use App\Models\Professors\Gender;
use App\Models\Professors\LevelAssessment;
use App\Models\Professors\ProfessorAddress;
use App\Models\User;
use App\Models\Exam_question\Exam;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class, 'gender_id');
    }

    public function addresses()
    {
        return $this->hasOne(StudentAddress::class);
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Languages::class, 'student_language', 'student_id', 'language_id');
    }

    public function levelAssessments()
    {
        return $this->hasMany(LevelAssessment::class);
    }

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'exam_students')
            ->withPivot([
                'payment_status',
//                'status',
                'score',
                'pass',
                'attempt_number',
                'started_at',
                'finished_at',
                'feedback',
            ])
            ->withTimestamps();
    }

//    public function full_name(): string
//    {
//        return $this->first_name . ' ' . $this->last_name;
//    }
//    public function likes()
//    {
//        return $this->morphMany(Like::class, 'likeable');
//    }


    public function full_name(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAgeGroupStudentAttribute()
    {
        if (!$this->date_of_birth) {
            return null; // یا مقدار دلخواه
        }

        $age = Carbon::parse($this->date_of_birth)->age;

        if ($age < 13) {
            return 1;
        } elseif ($age >= 13 && $age <= 19) {
            return 2;
        } else {
            return 3;
        }
    }

}




