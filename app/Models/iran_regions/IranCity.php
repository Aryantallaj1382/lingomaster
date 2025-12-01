<?php

namespace App\Models\iran_regions;

use App\Models\Country;
use SaliBhdr\TyphoonIranCities\Models\IranCity as BaseModel;

/**
 * Class IranCity (Shahr)
 */
class IranCity extends BaseModel
{
    public function country()
    {
        return $this->belongsTo(Country::class, 'countries_id', 'id');
    }
}

