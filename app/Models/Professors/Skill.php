<?php

namespace App\Models\Professors;

use App\Models\PlacementTest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    use HasFactory;
    protected $table = 'skills';
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'professor_skill', 'skill_id', 'professor_id');
    }

    public function placementTests()
    {
        return $this->belongsToMany(
            PlacementTest::class,
            'placement_test_skills',
            'skill_id',
            'placement_test_id'
        );
    }
}
