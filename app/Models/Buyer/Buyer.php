<?php
namespace App\Models\Buyer;

use App\Models\contacts\api;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Buyer extends Model
{

    protected $guarded = [];
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function addresses()
    {
        return $this->hasMany(BuyerAddress::class);
    }
    public function getProfileAttribute($value)
    {
        return $value ? api::api . $value : null;
    }
}
