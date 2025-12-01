<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountForStudent extends Model
{
    protected $table = 'discounts_for_student';

    protected $fillable = [
        'code', 'percentage', 'limit', 'expires_at', 'discountable_id', 'discountable_type'
    ];

    public function discountable()
    {
        return $this->morphTo();
    }
}
