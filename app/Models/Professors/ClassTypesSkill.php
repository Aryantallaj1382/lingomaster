<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTypesSkill extends Model
{
    use HasFactory;

    protected $fillable = ['class_type_id', 'class_type_option_id'];
protected $table = 'class_types_skill';
    public function classType()
    {
        return $this->belongsTo(ClassType::class);
    }

    public function classTypeOption()
    {
        return $this->belongsTo(ClassTypeOption::class);
    }
    public function placementTests()
    {
        return $this->belongsToMany(PlacementTest::class, 'placement_test_skill', 'skill_id', 'placement_test_id');
    }

}
