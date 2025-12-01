<?php

namespace App\Models;

use App\Models\Professors\SpecialDay;
use App\Models\Professors\Webinar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountForworkShop extends Model
{
    use HasFactory;

    protected $table = 'discounts_for_work_shop';

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
