<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class privetTeacherTimeSlot extends Model
{
    use HasFactory;
    protected $table = 'privet_teacher_time_slot';
    protected $guarded = [];
    protected $casts = [
        'time' => 'datetime',
    ];

    public function teacherTimeSlot()
    {
        return $this->belongsTo(TeacherTimeSlot::class, 'teacher_time_slot_id');
    }
    public function PrivetClassReservation()
    {
        return $this->belongsTo(PrivetClassReservation::class,'privet_class_reservation_id');
    }

}
