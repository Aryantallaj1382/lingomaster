<?php
namespace App\Models\Professors;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherTimeSlot extends Model
{
    use HasFactory;

    protected $fillable = ['teacher_id', 'date', 'time', 'min_blocks', 'status'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Professor::class, 'teacher_id');
    }

    public function privetClassReservations()
    {
        return $this->belongsToMany(
            PrivetClassReservation::class,
            'privet_teacher_time_slot',
            'teacher_time_slot_id',
            'privet_class_reservation_id'
        );
    }

}
