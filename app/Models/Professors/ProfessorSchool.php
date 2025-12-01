<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorSchool extends Model
{
    protected $table = 'professor_school_language';

    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class, 'language_id');
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }
    public function schoolLanguage(): BelongsTo
    {
        return $this->belongsTo(schoolLanguages::class);
    }

}
