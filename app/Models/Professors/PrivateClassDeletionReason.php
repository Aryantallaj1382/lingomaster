<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrivateClassDeletionReason extends Model
{
    use HasFactory;
    protected $table = 'private_class_deletion_reasons';
    protected $guarded = [];

    public function privet(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PrivetClassReservation::class , 'privet_id');
    }
}
