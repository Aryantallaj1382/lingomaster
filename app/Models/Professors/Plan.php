<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table ='plans';
    protected $fillable = [
        'title',
        'description',
        'base_price',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }


    public function getPriceForDuration($duration)
    {
        switch ($duration) {
            case '1_month':
                return $this->base_price;
            case '3_months':
                return $this->base_price * 3 * 0.9;
            case '6_months':
                return $this->base_price * 6 * 0.85;
            case '1_year':
                return $this->base_price * 12 * 0.75;
            default:
                return $this->base_price;
        }
    }
}
