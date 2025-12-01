<?php

namespace App\Models\Professors;

use App\Models\AgeGroup;
use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgeGroupLanguageLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'professor_id',
        'language_id',
        'age_group_id',
        'language_level_id',
    ];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function ageGroup()
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function languageLevel()
    {
        return $this->belongsTo(LanguageLevel::class);
    }


    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
}
