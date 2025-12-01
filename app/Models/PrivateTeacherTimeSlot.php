<?php
namespace App\Models;

use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\TeacherTimeSlot;
use Illuminate\Database\Eloquent\Model;

class PrivateTeacherTimeSlot extends Model
{

    protected $table = 'privet_teacher_time_slot';

    protected $guarded=[];

    public function classReservation()
    {
        return $this->belongsTo(PrivetClassReservation::class, 'privet_class_reservation_id');
    }

    public function teacherTimeSlot()
    {
        return $this->belongsTo(TeacherTimeSlot::class, 'teacher_time_slot_id');
    }

    public function reportRegistrations()
    {
        return $this->hasMany(ReportRegistration::class, 'privet_teacher_time_slot');
    }


}
