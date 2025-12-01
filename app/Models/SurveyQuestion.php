<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyQuestion extends Model
{
    protected $fillable = [
        'class_type',
        'question',
        'order',
    ];

    public $timestamps = true;
}
