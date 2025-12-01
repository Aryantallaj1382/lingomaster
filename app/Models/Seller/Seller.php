<?php

namespace App\Models\Seller;

use App\Models\order\Order;
use App\Models\SellerAccount;
use App\Models\shop\EducationalProduct;
use App\Models\shop\Product;
use App\Models\shop\Story;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Seller extends Model
{
    use HasFactory;

    protected $guarded = [];
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_seller');
    }

    public function address(): HasMany
    {
        return $this->hasMany(SellerAddress::class);
    }

    public function educationalProducts(): BelongsToMany
    {
        return $this->belongsToMany(EducationalProduct::class, 'educational_product_seller');
    }

    public function stories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class);
    }

    protected $appends = ['experience']; // تا به صورت خودکار در خروجی JSON بیاد

    public function getExperienceAttribute()
    {
        $createdAt = $this->created_at;
        $now = \Carbon\Carbon::now();

        $diffInMonths = $createdAt->diffInMonths($now, false); // مقدار واقعی (مثلاً 1.7)
        $diffInDays = $createdAt->diffInDays($now);

        $roundedMonths = round($createdAt->floatDiffInMonths($now)); // برای رند کردن ماه‌ها

        return $roundedMonths < 1
            ? round($diffInDays) . ' روز'
            : $roundedMonths . ' ماه';
    }

    public function accounts()
    {
        return $this->hasMany(SellerAccount::class);
    }


}
