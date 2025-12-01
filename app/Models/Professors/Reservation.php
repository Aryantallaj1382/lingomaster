<?php
namespace App\Models\Professors;

use App\Models\Languages;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = ['teacher_time_slot_id', 'type', 'class_mode'];

    public function students()
    {
        return $this->belongsToMany(User::class, 'reservation_student', 'reservation_id', 'student_id');
    }
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TeacherTimeSlot::class, 'teacher_time_slot_id');
    }
    public function language()
    {
        return $this->belongsTo(Languages::class, 'language_id');
    }

}
