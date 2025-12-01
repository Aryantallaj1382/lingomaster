<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PossibleTimeSlot extends Model
{
    use HasFactory;
    protected $fillable = ['date', 'day_of_week', 'time'];

}
