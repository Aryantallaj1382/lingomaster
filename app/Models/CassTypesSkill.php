<?php

namespace App\Models;

use App\Models\Professors\PlacementTest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CassTypesSkill extends Model
{
    use HasFactory;

    protected $fillable = ['title'];

    public function placementTests()
    {
        return $this->belongsToMany(PlacementTest::class, 'placement_test_skill');
    }
}
