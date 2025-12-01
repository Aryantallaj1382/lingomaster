<?php

namespace App\Models;

use App\Models\Professors\ClassType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTypeOption extends Model
{
    use HasFactory;

    protected $fillable = ['type'];

    public function classTypes()
    {
        return $this->belongsToMany(ClassType::class, 'class_type_class', 'class_type_option_id', 'class_type_id');
    }

}
