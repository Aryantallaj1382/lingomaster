<?php

namespace App\Models\Professors;


use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorAccentAndPronunciation extends Model
{
    use HasFactory;

    protected $table = 'professor_accent_and_pronunciation';

    protected $fillable = [
        'professor_id',
        'language_id',
        'accent_id',
    ];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function accentAndPronunciation()
    {
        return $this->belongsTo(AccentAndPronunciation::class, 'accent_id');
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
}
