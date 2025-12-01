<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VideoBaner extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'path','cover'];


}
