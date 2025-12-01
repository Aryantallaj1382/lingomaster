<?php

namespace App\Models;

use App\Models\iran_regions\IranCity;
use App\Models\Professors\Professor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name'];

    // رابطه با اساتید
    public function professors(): HasMany
    {
        return $this->hasMany(Professor::class);
    }

    public function cities()
    {
        return $this->hasMany(IranCity::class, 'countries_id', 'id');
    }
}

