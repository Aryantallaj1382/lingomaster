<?php

namespace App\Models;

use App\Models\shop\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCodeShop extends Model
{
    use HasFactory;
    protected $table = 'discount_code_shop';
    protected $guarded =[];

    protected $dates = ['expire_at'];

    // رابطه با فروشنده (User)
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    // رابطه با دسته‌بندی
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
