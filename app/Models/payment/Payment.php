<?php

namespace App\Models\payment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id', 'gateway_id', 'payable_id', 'payable_type',
        'amount', 'authority', 'ref_id', 'status', 'description', 'meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function payable()
    {
        return $this->morphTo(); // اتصال به هر مدل قابل پرداخت
    }}
