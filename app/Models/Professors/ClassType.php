<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassType extends Model
{
    use HasFactory;


    protected $guarded = [];
    protected $table = 'class_types';


    public function classOptions()
    {
        return $this->belongsToMany(ClassTypeOption::class, 'class_type_class', 'class_type_id', 'class_type_option_id');
    }


    // رابطه با PlacementTest از طریق ClassTypeSkill
    public function placementTests()
    {
        return $this->hasMany(PlacementTest::class, 'class_type_id');
    }


    public function language()
    {
        return $this->belongsTo(Languages::class, 'language_id');
    }
    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }
}
