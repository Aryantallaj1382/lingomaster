<?php

namespace App\Models\Professors;

use App\Models\shop\Accent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorLanguage extends Model
{
    use HasFactory;
    protected $fillable = ['professor_id', 'language_id', 'accents'];

    public function accents()
    {
        return $this->belongsToMany(Accent::class, 'professor_language_accent', 'professor_language_id', 'accent_id');
    }
}
