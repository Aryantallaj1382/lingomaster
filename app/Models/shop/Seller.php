<?php

namespace App\Models\shop;

use App\Models\order\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class   Seller extends Model
{
    use HasFactory;

    protected $fillable = [
        'title'
    ];
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_seller');
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

}
