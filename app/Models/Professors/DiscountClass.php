<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountClass extends Model
{
    use HasFactory;
    protected $table ='discounts';
    protected $guarded =[];
    public function Professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function specialday()
    {
        return $this->belongsTo(SpecialDay::class, 'special_day_id');
    }
}

