<?php

namespace App\Models\shop;

use App\Models\contacts\api;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Slaider extends Model
{
    use HasFactory;
    protected $fillable =[
        'link',
        'tablet_image',
        'laptop_image',
        'mobile_image',
    ];
    protected $table = 'slaiders';
    public function getTabletImageAttribute($value)
    {
        return $value ?api::api . $value : null;
    }

    public function getLaptopImageAttribute($value)
    {
        return $value ? api::api . $value : null;
    }
    public function getMobileImageAttribute($value)
    {
        return $value ?api::api . $value : null;
    }
}
