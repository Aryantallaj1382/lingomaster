<?php
namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class WalletSetting extends Model
{
    use HasFactory;

    // برای استفاده از UUID به جای ID معمولی
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'wallet_id',
        'minimum_deposit',
        'maximum_withdrawal',
    ];

    protected static function booted()
    {
        static::creating(function ($walletSetting) {
            $walletSetting->id = (string) Str::uuid();
        });
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
