<?php

namespace App\Models\Professors;

use App\Models\shop\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LearningSubgoal extends Model
{
    use HasFactory;
    protected $fillable = ['goal_id', 'title','sub'];

    public function goal()
    {
        return $this->belongsTo(LearningGoal::class, 'goal_id');
    }
    public function products(): HasMany|LearningSubgoal
    {
        return $this->hasMany(Product::class, 'learning_subgoal_id');
    }
    public function group_class(): HasMany
    {
        return $this->hasMany(GroupClass::class);
    }
    public function bookLearnings()
    {
        return $this->hasMany(BookLearning::class, 'subgoal_id');
    }


}
