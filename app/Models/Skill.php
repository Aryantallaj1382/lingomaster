<?php
namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = ['name'];

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
    public function placementTests()
    {
        return $this->belongsToMany(
            PlacementTest::class,
            'placement_test_skills',
            'skill_id',
            'placement_test_id'
        );
    }
}
