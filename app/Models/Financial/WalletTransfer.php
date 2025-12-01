<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletTransfer extends Model
{
    use HasFactory;

    // برای استفاده از UUID به جای ID معمولی
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'from_wallet_id',
        'to_wallet_id',
        'amount',
        'status',
    ];

    // وقتی یک مدل جدید ایجاد می‌شود، به طور خودکار UUID برای آن تولید می‌شود
    protected static function booted()
    {
        static::creating(function ($walletTransfer) {
            $walletTransfer->id = (string) Str::uuid();
        });
    }

    // رابطه با مدل Wallet برای کیف پول ارسال‌کننده
    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    // رابطه با مدل Wallet برای کیف پول گیرنده
    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }
}
