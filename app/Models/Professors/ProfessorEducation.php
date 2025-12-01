<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorEducation extends Model
{
    use HasFactory;
    protected $table = 'professor_educations';

    protected $guarded = [];

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professors_id');
    }
}
