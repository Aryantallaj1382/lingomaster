<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Baner extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'url','product_id'];
    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function getProductImageAttribute()
    {
        return $this->product?->first_image ?? null;
    }

    public function getProductPriceAttribute()
    {
        return $this->product?->price ?? null;
    }
    public function getProductCategoryTitleAttribute()
    {
        return $this->product?->category?->title;
    }
    public function getSelectedSellerAttribute()
    {
        return $this->product?->selected_seller;
    }
}
