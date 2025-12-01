<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Publication extends Model
{
    use HasFactory;
    protected $fillable = ['title'];
    public function product(): HasMany
    {
        return $this->hasMany(Product::class, 'publication_id');
    }
}
