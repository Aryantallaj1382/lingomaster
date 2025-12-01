<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductType extends Model
{
    use HasFactory;

    protected  $table = 'product_type';

    protected $fillable = [
        'title'
    ];

    public function product(): HasMany
    {
        return $this->hasMany(Product::class, 'product_type_id');
    }
}
