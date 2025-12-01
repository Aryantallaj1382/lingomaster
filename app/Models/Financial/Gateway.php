<?php

namespace App\Models\Financial;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config' => 'array', // Assuming config is stored as JSON
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
