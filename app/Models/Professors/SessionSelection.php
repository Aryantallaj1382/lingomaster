<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionSelection extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // رابطه با affordable_classes
    public function affordableClasses(): HasMany
    {
        return $this->hasMany(AffordableClass::class, 'session_selection_id');
    }

    // رابطه با assessment_types
    public function assessmentTypes(): HasMany
    {
        return $this->hasMany(AssessmentType::class, 'session_selection_id');
    }
}
