<?php

namespace App\Models\Financial;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gateway_id',
        'payable_id',
        'payable_type',
        'amount',
        'authority',
        'ref_id',
        'status',
        'description',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array', // Assuming meta is stored as JSON
    ];

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }

    public function payable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
