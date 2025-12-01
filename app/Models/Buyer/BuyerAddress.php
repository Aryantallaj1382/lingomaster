<?php

namespace App\Models\Buyer;

use App\Models\Country;
use App\Models\iran_regions\IranCity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuyerAddress extends Model
{
    protected $guarded = [];
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
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
            '%s, %s, منطقه %s, واحد %s, پلاک %s, کد پستی %s',
            $this->country->title ?? 'کشور نامشخص',
            $this->city->name ?? 'شهر نامشخص',
            $this->region ?? 'منطقه نامشخص',
            $this->unit ?? 'واحد نامشخص',
            $this->plaque ?? 'پلاک نامشخص',
            $this->postal_code ?? 'کد پستی نامشخص'
        ));
    }

}
