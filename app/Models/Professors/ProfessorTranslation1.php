<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorTranslation1 extends Model
{
    use HasFactory;

    protected $table = 'professors_translations';

    protected $guarded = [];

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professors_id');
    }
    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class);
    }
}
