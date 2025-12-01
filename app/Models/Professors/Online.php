<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Online extends Model
{
    use HasFactory;

    protected $guarded = [];

    /**
     * رابطه با زبان
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class);
    }

    /**
     * رابطه با استاد
     */
    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }
}
