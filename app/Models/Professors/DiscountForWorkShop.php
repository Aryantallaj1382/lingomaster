<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountForWorkShop extends Model
{
    use HasFactory;

    protected $table = 'discounts_for_work_shop';

    protected $guarded = [];



    public function workShop()
    {
        return $this->belongsTo(WorkShop::class, 'work_shop_id');
    }

    public function specialDay()
    {
        return $this->belongsTo(SpecialDay::class, 'special_day_id');
    }
}
