<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccentAndPronunciation extends Model
{
    use HasFactory;

    protected $table = 'accent_and_pronunciation';

    protected $fillable = [
        'title',
    ];

    public function professors()
    {
        return $this->belongsToMany(Professor::class, 'professor_accent_and_pronunciation')
            ->withPivot('language_id')
            ->withTimestamps();
    }
}
