<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorLearningGoal extends Model
{
    use HasFactory;
    protected $fillable = ['professor_id', 'language_id', 'subgoal_id'];

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class);
    }

    public function subgoal(): BelongsTo
    {
        return $this->belongsTo(LearningSubgoal::class, 'subgoal_id');
    }
}
