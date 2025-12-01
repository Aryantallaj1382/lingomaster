<?php

namespace App\Models\Exam_question;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student\Student;
use App\Models\payment\Payment;

class ExamReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'student_id',
        'payment_status',
        'status',
        'discount_code',
        'final_price',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }
}
