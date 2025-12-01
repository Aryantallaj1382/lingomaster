<?php

namespace App\Models\ExamPayment;
use App\Models\Financial\Payment;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderExamPayment extends Model
{

    protected $table = 'OrderExamPayments';
    protected $fillable = [
    'user_id',
    'username',
    'password',
    'exam_location',
    'exam_date',
    'exam_time',
    'description',
    'exam_payment_id',
    ];
    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function examPayment(): BelongsTo
    {
        return $this->belongsTo(ExamPayment::class, 'exam_payment_id');
    }
}
