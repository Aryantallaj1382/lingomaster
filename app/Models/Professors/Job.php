<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Job extends Model
{
    protected $fillable = ['title'];
    use HasFactory;
    public function professors(): BelongsToMany
    {
        return $this->belongsToMany(Professor::class, 'professor_job', 'job_id', 'professor_id');
    }
}
