<?php

namespace App\Models\shop;

use App\Models\Professors\ProfessorBook;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subjects extends Model
{
    use HasFactory;
    protected  $table = 'subjects';

    protected $fillable = [
        'title'
    ];

    public function product(): HasMany
    {
        return $this->hasMany(Product::class, 'subject_id');
    }
    public function professor_book(): HasMany
    {
        return $this->hasMany(ProfessorBook::class, 'subject_id');
    }

}
