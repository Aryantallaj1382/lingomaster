<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorBlog extends Model
{
    use HasFactory;

    protected $table = 'professors_blogs';

    protected $guarded = [];


    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professors_id');
    }
    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
}
