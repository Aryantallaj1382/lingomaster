<?php
namespace App\Models\Exam_question;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Like;
use App\Models\shop\Comment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Exam extends Model
{
    protected $fillable = [
        'skill_id','language_id', 'name', 'description', 'level',
        'type', 'duration_minutes', 'is_active'
    ];
        protected $casts = [
        'tags' => 'array',
        'summaries' => 'array',
        'questions' => 'array'
    ];


    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function parts()
    {
        return $this->hasMany(ExamPart::class ,'exam_id');
    }

    public function total_price()
    {
        return $this->price *  ($this->discount/100);
    }


    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function examStudents()
    {
        return $this->belongsToMany(ExamStudent::class)
            ->withPivot([
                'reportCardPath',
                'pass' ,
                'status',
                'score',
                'attempt_number',
                'started_at',
                'finished_at',
                'feedback',
            ]);
    }
    public function getImageAttribute($value)
    {
        return $value ? api::api . $value : null;
    }
}
