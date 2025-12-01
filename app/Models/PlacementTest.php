<?php

namespace App\Models;

use App\Models\Professors\Professor;
use App\Models\Professors\Skill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PlacementTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'professor_id',
        'language_id',
        'age_group_id',
        'price',
    ];
    protected $appends = ['price_fa'];

    public function getPriceFaAttribute(): string
    {
        return match($this->price) {
            'free' => '1',
            '50_percent' => '2',
            'no_discount' => '3',
            default => 'نامشخص'
        };
    }
    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }

    public function ageGroup()
    {
        return $this->belongsTo(AgeGroup::class);
    }


    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'placement_test_skill', 'placement_test_id', 'skill_id');
    }
}
