<?php

namespace App\Models\Professors;

use App\Models\Country;
use App\Models\GeographicRegion;
use App\Models\iran_regions\IranCity;
use App\Models\Languages;
use App\Models\RegionProfessor;
use App\Models\shop\Videos;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offline extends Model
{
    use HasFactory;

    protected $guarded=[];
    protected $casts = [
        'areas' => 'array',
    ];


    /**
     * رابطه با زبان
     */
    public function language()
    {
        return $this->belongsTo(Languages::class);
    }

    /**
     * رابطه با استاد
     */
    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    /**
     * رابطه با کشور
     */
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * رابطه با شهر
     */
    public function city()
    {
        return $this->belongsTo(IranCity::class, 'city_id');
    }

    public function geographicRegions()
    {
        return $this->belongsToMany(
            GeographicRegion::class,
            'geographic_region_offline_class', // اسم جدول واسط
            'offline_class_id',               // اسم ستون در جدول واسط که به این مدل اشاره داره
            'geographic_region_id'            // اسم ستون در جدول واسط که به مدل GeographicRegion اشاره داره
        );
    }

    public function regions()
    {
        return $this->hasMany(GeographicRegion::class, 'offline_class_id');
    }

}
