<?php

namespace App\Models\Professors;

use App\Models\AgeGroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LanguageLevel extends Model
{
    use HasFactory;

    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(
            Professor::class,
            'professor_language_level',
            'language_levels_id',
            'professor_id'
        )->withPivot('language_id'); // اضافه کردن language_id به pivot table
    }
    public function ageGroups()
    {
        return $this->belongsToMany(AgeGroup::class, 'age_group_language_levels')
            ->withPivot('professor_id', 'language_id')
            ->withTimestamps();
    }
    public function scopeOrderedByCustomIds($query)
    {
        $orderedIds = [4, 1, 2, 3];
        return $query->orderByRaw('FIELD(id, ' . implode(',', $orderedIds) . ')');
    }

}
