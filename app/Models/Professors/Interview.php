<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

    protected $table = 'interview';

    protected $fillable = [
        'title',
    ];

    public function professors()
    {
        return $this->belongsToMany(Professor::class, 'professor_interview')
            ->withPivot('language_id')
            ->withTimestamps();
    }
}
