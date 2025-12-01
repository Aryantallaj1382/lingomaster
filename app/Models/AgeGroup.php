<?php

namespace App\Models;

use App\Models\Professors\LanguageLevel;
use App\Models\Professors\Professor;
use App\Models\Professors\ProfessorBook;
use App\Models\shop\Product;
use App\Models\shop\Tools;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgeGroup extends Model
{
    use HasFactory;

    protected  $table = 'age_groups';

    protected $guarded= [];
    public function product(): HasMany
    {
        return $this->hasMany(Product::class, 'age_group_id');
    }
    public function Tools(): HasMany
    {
        return $this->hasMany(Tools::class, 'age_group_id');
    }
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class,
            'professor_language_level', 'language_level_id', 'professor_id')->withPivot('language_id');
    }
    public function professor_book(): HasMany
    {
        return $this->hasMany(ProfessorBook::class, 'age_group_id');
    }

    public function languageLevels()
    {
        return $this->belongsToMany(LanguageLevel::class, 'age_group_language_levels')
            ->withPivot('professor_id', 'language_id')
            ->withTimestamps();
    }

}
