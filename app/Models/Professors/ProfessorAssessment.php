<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class ProfessorAssessment extends Model
{
    protected $fillable = ['professor_id', 'assessment_type_id', 'discount_type_id', 'price'];

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function assessmentType(): BelongsTo
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function discountType(): BelongsTo
    {
        return $this->belongsTo(DiscountType::class);
    }
}

