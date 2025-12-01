<?php

namespace App\Models\Seller;
use App\Models\order\OrderItem;
use App\Models\shop\Product;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSeller extends Model
{
    protected $table = 'product_seller';

    protected $fillable = [
        'product_id',
        'seller_id',
        'quantity',
        'price',
        'discounted_price',
        'discount_expire_at',
    ];

    protected $casts = [
        'discount_expire_at' => 'datetime',
    ];

    // ارتباط با محصول
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ارتباط با فروشنده
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
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

    public function getSalesDataAttribute()
    {
        // دریافت ID محصول مرتبط با این فروشنده
        $productId = $this->product_id;

        // دریافت تعداد فروش و مبلغ کل
        $salesData = OrderItem::whereHas('order', function ($query) {
            $query->where('payment_status', 'paid');
        })
            ->where('product_id', $productId)
            ->get()
            ->groupBy('product_id')
            ->map(function ($items) {
                $quantity = $items->sum('quantity'); // مجموع تعداد فروش
                $totalAmount = $quantity * $this->discounted_price; // کل مبلغ دریافتی
                return [
                    'quantity_sold' => $quantity,
                    'total_amount' => $totalAmount,
                ];
            });

        return $salesData[$productId] ?? [
            'quantity_sold' => 0,
            'total_amount' => 0,
        ];
    }
}
