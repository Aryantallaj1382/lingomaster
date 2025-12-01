<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountForGroupClass extends Model
{
    use HasFactory;

    protected $table = 'discounts_for_group_class';

    protected $guarded = [];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function groupClass()
    {
        return $this->belongsTo(GroupClass::class, 'group_classes_id');
    }

    public function specialDay(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SpecialDay::class, 'special_day_id');
    }

}
