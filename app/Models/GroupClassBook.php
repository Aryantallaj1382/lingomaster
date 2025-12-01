<?php

namespace App\Models;

use App\Models\shop\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupClassBook extends Model
{
    use HasFactory;

    protected $fillable = ['group_class_id', 'product_id', 'name', 'book_type', 'book_link', 'type'];

    public function groupClass()
    {
        return $this->belongsTo(GroupClass::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

