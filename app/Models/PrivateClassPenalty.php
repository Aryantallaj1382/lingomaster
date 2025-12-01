<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateClassPenalty extends Model
{
    use HasFactory;
    protected $table = 'private_class_Penalty';
    protected $guarded = [];

    public function privet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PrivetClassReservation::class , 'privet_id');
    }}
