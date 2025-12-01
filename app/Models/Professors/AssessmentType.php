<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentType extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'session_selection_id'];

    // رابطه معکوس با session_selection
    public function sessionSelection(): BelongsTo
    {
        return $this->belongsTo(SessionSelection::class, 'session_selection_id');
    }

}
