<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorTechnique extends Model
{
    protected $table = 'professor_language_technique';

    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class, 'language_id'); // ارتباط با جدول Languages
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }
    public function technique()
    {
        return $this->belongsTo(LanguageTechnique::class, 'language_technique_id');
    }

}
