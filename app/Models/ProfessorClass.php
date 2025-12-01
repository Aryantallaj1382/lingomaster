<?php

namespace App\Models;

use App\Models\Professors\Professor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorClass extends Model
{

    use HasFactory;
    protected $table = 'professor_classes';
    protected $fillable = [
        'language_id',
        'professor_id',
        'webinar',
        'workshop',
        'private_class',
        'group_class',
    ];
    public function language()
    {
        return $this->belongsTo(Languages::class, 'language_id');
    }

    // رابطه با مدل Professor
    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }
}
