<?php
namespace App\Models\Professors;

use App\Models\Country;
use App\Models\iran_regions\IranCity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessorAddress extends Model
{
    protected $guarded=[];

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(IranCity::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
    public function gruopClass()
    {
        return $this->hasMany(GroupClass::class, 'address_id');
    }
}
