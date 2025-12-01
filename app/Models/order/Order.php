<?php

namespace App\Models\order;

use App\Models\Buyer\BuyerAddress;
use App\Models\Financial\Payment;
use App\Models\Seller\Seller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;
    public $guarded = [];
    protected $casts = [
        'is_returned' => 'boolean',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
    public function buyerAddress()
    {
        return $this->belongsTo(BuyerAddress::class, 'buyer_address_id');
    }
    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public static function generateUniqueTrackingCode($length = 8)
    {
        do {
            $code = Str::upper(Str::random($length)); // مثلا کد 10 حرفی بزرگ
        } while (self::where('tracking_code', $code)->exists());

        return $code;
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->tracking_code)) {
                $order->tracking_code = self::generateUniqueTrackingCode();
            }
        });
    }

    protected static function booted()
    {
        static::updating(function ($order) {
            if ($order->isDirty('seller_approval_status') && $order->seller_approval_status === 'shipped') {
                $order->delivery_date = Carbon::now();
            }
            if ($order->isDirty('status') && $order->status === 'returned') {
                $order->returned_date = Carbon::now();
            }
            if ($order->isDirty('seller_approval_status') && $order->seller_approval_status === 'shipped') {
                $order->status = 'delivered';
            }
        });
    }


}
