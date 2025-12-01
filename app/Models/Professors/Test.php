<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Test extends Model
{
    use HasFactory;
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'professor_test', 'test_id', 'professor_id');
    }
}
