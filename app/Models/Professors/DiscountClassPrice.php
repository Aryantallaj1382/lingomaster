<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountClassPrice extends Model
{
    use HasFactory;

    protected $guarded=[];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function classPrice()
    {
        return $this->belongsTo(ClassPrice::class);
    }

    public function specialDay()
    {
        return $this->belongsTo(SpecialDay::class);
    }
}
