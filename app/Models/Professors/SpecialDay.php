<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecialDay extends Model
{
    use HasFactory;

    protected $guarded =[];
    protected $table = 'special_days';

    public function discounts()
    {
        return $this->hasMany(DiscountClass::class, 'special_day_id');
    }
}
