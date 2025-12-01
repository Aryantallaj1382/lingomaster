<?php

namespace App\Models\Professors;

use App\Models\Languages;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassPrice extends Model
{
    use HasFactory;
    protected $table = 'class_prices';
    protected $guarded= [];

    public function professor()
    {
        return $this->belongsTo(Professor::class);
    }

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }

    // در مدل ClassPrice:
    public function discount()
    {
        return $this->hasOne(DiscountClassPrice::class, 'class_price_id');
    }

}
