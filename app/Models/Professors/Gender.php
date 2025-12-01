<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Gender extends Model
{
    use HasFactory;
    protected $table = 'genders';
    protected $hidden = ['pivot'];

    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'professor_gender',
            'gender_id', 'professor_id')->withPivot('language_id');
    }
}
