<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTypeOption extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function classTypes()
    {
        return $this->belongsToMany(ClassType::class, 'class_type_class', 'class_type_option_id', 'class_type_id');
    }

}
