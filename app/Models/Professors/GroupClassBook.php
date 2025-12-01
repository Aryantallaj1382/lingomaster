<?php

namespace App\Models\Professors;

use App\Models\shop\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupClassBook extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function groupClass()
    {
        return $this->belongsTo(GroupClass::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function bookLearning()
    {
        return $this->belongsTo(BookLearning::class, 'book_learning_id');
    }

    public function pay_group_reservation($reservationId)
    {
//        $studentId = auth()->user()->student->id;
//        Log::info($studentId);

        // پیدا کردن رزرو مربوطه
        $reservation = GroupClassReservation::where('group_class_id', $reservationId)
//            ->where('student_id', $studentId)
            ->where('payment_status', 'pending')
            ->firstOrFail();

        $group = $reservation->groupClass;

        $amount = $group->total_price; // یا محاسبه با تخفیف در صورت وجود
        $description = "پرداخت رزرو کلاس گروهی: " . $group->subject->title;

        $paymentService = new zarinpal();
//        Log::info($reservation, $amount, $description);
        $call_back = 'zarin_callback';
        return $paymentService->pay($reservation, $amount, $description , $call_back);
    }

}

