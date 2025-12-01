<?php

namespace App\Models;

use App\Models\Professors\Skill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlacementTestSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'placement_test_id',
        'skill_id',
    ];
    protected $table = 'placement_test_skill';

    public function placementTest()
    {
        return $this->belongsTo(PlacementTest::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }
}
