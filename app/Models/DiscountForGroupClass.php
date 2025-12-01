<?php

namespace App\Models;

use App\Models\Professors\SpecialDay;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountForGroupClass extends Model
{
    use HasFactory;

    protected $table = 'discounts_for_group_class';

    protected $fillable = [
        'group_classes_id',
        'class_mode',
        'discount_type',
        'discount_percentage',
        'start_date',
        'end_date',
        'first_few_count',
        'special_day_id',
        'description',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function groupClass()
    {
        return $this->belongsTo(GroupClass::class, 'group_classes_id');
    }

    public function specialDay()
    {
        return $this->belongsTo(SpecialDay::class, 'special_day_id');
    }
}
