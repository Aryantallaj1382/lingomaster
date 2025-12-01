<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorAboutText extends Model
{
    protected $guarded= [];
    use HasFactory;
    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }

}
