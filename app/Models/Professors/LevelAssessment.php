<?php

namespace App\Models\Professors;

use App\Models\PrivateClassReservation;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LevelAssessment extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table ='level_assessments';
    protected $casts = [
        'solution' => 'array',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function privateClassReservation(): BelongsTo
    {
        return $this->belongsTo(PrivetClassReservation::class,'private_class_reservation_id');
    }
}
