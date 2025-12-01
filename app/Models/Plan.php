<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;
    protected $table = 'plans';
    protected $guarded = [];
    protected $casts = [
        'features' => 'array',
    ];

    public function durations() {
        return $this->hasMany(PlanDuration::class);
    }
}
