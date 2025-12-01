<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorGender extends Model
{
    use HasFactory;
    protected $table = 'professor_gender';
    public function professors()
    {
        return $this->hasMany(Professor::class);
    } public function gender()
{
    return $this->hasMany(Gender::class);
}
}
