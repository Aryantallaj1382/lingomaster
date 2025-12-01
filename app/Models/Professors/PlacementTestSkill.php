<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlacementTestSkill extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function placementTest()
    {
        return $this->belongsTo(PlacementTest::class);
    }

    public function skill()
    {
        return $this->belongsTo(ClassTypesSkill::class, 'skill_id');
    }

}
