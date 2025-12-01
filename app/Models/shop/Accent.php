<?php

namespace App\Models\shop;

use App\Models\Languages;
use App\Models\Professors\Professor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Accent extends Model
{
    use HasFactory;
    protected  $table = 'accents';

    protected $guarded = [
    ];
    public function product(): HasMany
    {
        return $this->hasMany(Product::class, 'accent_id ');
    }
    public function language(): BelongsTo
    {
        return $this->belongsTo(Languages::class,'languages_id');
    }

    public function professors()
    {
        return $this->belongsToMany(Professor::class, 'accents_professor', 'accent_id', 'professor_id');
    }

}
