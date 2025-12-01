<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProductSeller extends Model
{
    use HasFactory;
    protected $table = 'product_seller';
    protected $guarded = [];
    public function seller()
    {
        return $this->belongsTo(\App\Models\Seller\Seller::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function discountPercentage(): Attribute
    {
        return Attribute::get(function () {
            if ($this->discounted_price && $this->price > 0) {
                $discount = $this->price - $this->discounted_price;
                return round(($discount / $this->price) * 100);
            }
            return 0;
        });
    }
    public function scopeWithFinalPrice($query)
    {
        return $query->select(DB::raw('COALESCE(discounted_price, price) as final_price'));
    }
    public function getFinalPriceAttribute()
    {
        return $this->discounted_price ?? $this->price;
    }
}
