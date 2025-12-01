<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorUniversityLanguage extends Model
{
    protected $table = 'professor_university_language';
    protected $fillable = [
        'professor_id',
        'university_language_id',
        'language_id',
    ];
    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    public function universityLanguage()
    {
        return $this->belongsTo(UniversityLanguage::class, 'university_language_id');
    }

    public function language()
    {
        return $this->belongsTo(Languages::class, 'language_id');
    }
}
