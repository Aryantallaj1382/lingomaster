<?php

namespace App\Models;

use App\Models\Seller\Seller;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'account_number',
        'bank_name',
        'is_default',
        'shaba'
    ];

    /**
     * ارتباط با فروشنده
     */
    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
    protected function accountNumber(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (strlen($value) === 16) {
                    return substr($value, 0, 4) . ' **** **** ' . substr($value, -4);
                }

                // اگر شماره حساب 16 رقمی نبود، همون مقدار خام رو برگردون
                return $value;
            },
        );
    }

}
