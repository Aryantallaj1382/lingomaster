<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningGoal extends Model
{
    use HasFactory;
    protected $fillable = ['title'];

    public function subgoals(): HasMany
    {
        return $this->hasMany(LearningSubgoal::class, 'goal_id');
    }


}
