<?php

namespace App\Models\order;


use App\Models\shop\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    protected $appends = ['product_data'];

    public function getProductDataAttribute()
    {

        return $this->product ? [
            'id' => $this->product->id,
            'title' => $this->product->title,
            'image' => $this->product->image,
            'price' => $this->product->price,
        ] : null;
    }

}
