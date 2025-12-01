<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NearestClass extends Model
{
    protected $fillable = ['professor_id', 'language_id', 'type', 'title'];
    protected $table = 'nearest_classes';
    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
}
