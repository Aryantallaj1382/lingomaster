<?php

namespace App\Models\Seller;

use App\Models\Buyer\Buyer;
use App\Models\Country;
use App\Models\iran_regions\IranCity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SellerAddress extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(IranCity::class);
    }
    public function getFullAddressAttribute(): string
    {
        return trim(sprintf(
            '%s, %s, منطقه %s',
            $this->country->title ?? 'کشور نامشخص',
            $this->city->name ?? 'شهر نامشخص',
            $this->region ?? 'منطقه نامشخص',

        ));
    }

}
