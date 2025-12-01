<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorInterview extends Model
{
    use HasFactory;

    protected $table = 'professor_interview';

    protected $fillable = [
        'professor_id',
        'language_id',
        'interview_id',
    ];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
}
