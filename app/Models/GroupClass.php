<?php

namespace App\Models;

use App\Models\Professors\Gender;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningSubgoal;
use App\Models\shop\Product;
use App\Models\shop\Subjects;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GroupClass extends Model
{
    use HasFactory;
    protected $table = 'group_classes';

    protected $guarded = [];

    public function teachingType(): BelongsTo
    {
        return $this->belongsTo(TeachingType::class);
    }

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(LanguageLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(LearningSubgoal::class);
    }

    public function book()
    {
        return $this->belongsTo(Product::class);
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }

    public function schedules(): GroupClass|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GroupClassSchedule::class);
    }

    public function books()
    {
        return $this->hasMany(GroupClassBook::class);
    }
    public function discount(): HasOne
    {
        return $this->hasOne(DiscountForGroupClass::class, 'group_classes_id');
    }
}
