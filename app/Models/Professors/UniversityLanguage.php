<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UniversityLanguage extends Model
{
    use HasFactory;
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'professor_university_language', 'university_language_id', 'professor_id');
    }
}
