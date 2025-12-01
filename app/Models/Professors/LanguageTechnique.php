<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class LanguageTechnique extends Model
{
    use HasFactory;
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'professor_language_technique', 'language_technique_id', 'professor_id');
    }
}
