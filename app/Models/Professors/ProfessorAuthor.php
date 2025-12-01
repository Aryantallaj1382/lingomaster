<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorAuthor extends Model
{
    use HasFactory;

    protected $table = 'professors_author';

    protected $guarded = [];


    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class, 'professors_id');
    }
    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
}
