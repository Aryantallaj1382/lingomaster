<?php

namespace App\Models;

use App\Models\contacts\api;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'news';

    protected $fillable = [
        'title',
        'image',
        'tag',
        'text',
        'order',
        'view',
    ];

    protected $casts = [
        'tag' => 'array',
    ];
    public function getImageAttribute($value)
    {
        return $value ? api::api . $value : null;
    }
}
