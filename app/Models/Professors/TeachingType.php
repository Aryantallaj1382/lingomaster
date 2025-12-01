<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeachingType extends Model
{
    use HasFactory;
    protected $table = 'teaching_types';

    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'professor_teaching_type',
            'teaching_type_id', 'professor_id')->withPivot('language_id');
    }
    public function Offline(): HasMany
    {
        return $this->hasMany(Offline::class, 'teaching_types_id');
    }

    public function Online(): HasMany
    {
        return $this->hasMany(Online::class, 'teaching_types_id');
    }
}
