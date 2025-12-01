<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorLanguageLevel extends Model
{
    use HasFactory;

    protected $guarded;

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
}

