<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorTranslation extends Model
{
    protected $table = 'professor_translation';

    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class, 'language_id'); // ارتباط با جدول Languages
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }
    public function translation()
    {
        return $this->belongsTo(Translation::class, 'translation_id');
    }
}
