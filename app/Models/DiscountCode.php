<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    protected $fillable = [
        'code', 'type', 'value', 'max_discount',
        'min_purchase', 'start_at', 'expires_at',
        'usage_limit', 'used_count', 'is_active',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function isValid()
    {
        return $this->is_active &&
            ($this->start_at === null || $this->start_at <= now()) &&
            ($this->expires_at === null || $this->expires_at >= now()) &&
            ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function calculateDiscount(int $total)
    {
        if ($this->type === 'amount') {
            return min($this->value, $total);
        }

        // اگر درصدی است، فقط درصد را اعمال کن
        return floor(($this->value / 100) * $total);
    }

}
