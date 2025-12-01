<?php

namespace App\Models\Financial;

use App\Models\payment\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;


    protected $guarded = [];


    // رابطه با مدل Wallet
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
    public function payment()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

}
