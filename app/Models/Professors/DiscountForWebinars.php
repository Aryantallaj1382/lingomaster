<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountForWebinars extends Model
{
    use HasFactory;

    protected $table = 'discounts_for_webinars';

    protected $guarded = [];



    public function webinars()
    {
        return $this->belongsTo(Webinar::class, 'webinars_id');
    }

    public function specialDay()
    {
        return $this->belongsTo(SpecialDay::class, 'special_day_id');
    }
}
