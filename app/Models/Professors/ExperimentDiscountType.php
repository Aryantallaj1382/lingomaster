<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExperimentDiscountType extends Model
{
    protected $table = 'experiment_discount_types';

    protected $fillable = ['title'];
}
