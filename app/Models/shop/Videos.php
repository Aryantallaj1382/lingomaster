<?php

namespace App\Models\shop;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Videos extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'videos';


    public function videoable(): MorphTo
    {
        return $this->morphTo('videoable', 'videoable_type', 'videoable_id');
    }
    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class);
    }

}
