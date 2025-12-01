<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExperimentClass extends Model
{
    protected $table = 'experiment_classes';

    protected $fillable = [
        'professor_id',
        'experiment_discount_type_id',
        'title',
        'description',
    ];

    // رابطه با استاد
    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    // رابطه با نوع تخفیف کلاس آزمایشی
    public function discountType(): BelongsTo
    {
        return $this->belongsTo(ExperimentDiscountType::class, 'experiment_discount_type_id');
    }
}

