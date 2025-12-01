<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class schoolLanguages extends Model
{
    use HasFactory;
    protected $table = 'school_languages';
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'professor_school_language', 'school_language_id', 'professor_id');
    }
}
