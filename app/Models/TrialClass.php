<?php

namespace App\Models;

use App\Models\Professors\Professor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrialClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'professor_id',
        'language_id',
        'is_trial',
        'discount',
    ];

    // رابطه با استاد
    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }
    public function getDiscountTitleAttribute()
    {
        switch ($this->discount) {
            case 'free':
                return '1';
            case '50_percent':
                return '2';
            case 'no_discount':
                return '3';
            default:
                return null;
        }
    }
    public function getDiscountAttribute($value)
    {
        switch ($value) {
            case 'free':
                return '1';
            case '50_percent':
                return '2';
            case 'no_discount':
                return '3';
            default:
                return $value;
        }
    }




    // رابطه با زبان
    public function language()
    {
        return $this->belongsTo(Languages::class, 'language_id');
    }
}
