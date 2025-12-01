<?php

namespace App\Console\Commands;

use App\Models\Professors\PrivetClassReservation;
use App\Models\Professors\privetTeacherTimeSlot;
use App\Models\Professors\TeacherTimeSlot;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class ClearUnpaidReservations extends Command
{
    protected $signature = 'reservations:clear-unpaid';
    protected $description = 'حذف رزروهای پرداخت نشده بعد از نیم ساعت و آزادسازی تایم‌ها';

    public function handle()
    {
        $cutoff = Carbon::now()->subMinutes(10);

        $unpaidReservations = PrivetClassReservation::where('payment_status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($unpaidReservations as $reservation) {
            DB::transaction(function () use ($reservation) {
                $timeSlotIds = PrivetTeacherTimeSlot::where('privet_class_reservation_id', $reservation->id)
                    ->pluck('teacher_time_slot_id');

                PrivetTeacherTimeSlot::where('privet_class_reservation_id', $reservation->id)->delete();

                TeacherTimeSlot::whereIn('id', $timeSlotIds)->update(['status' => 'open']);

                $reservation->update(['payment_status' => 'failed']);
            });

            $this->info("رزرو شماره {$reservation->id} حذف و تایم‌ها آزاد شدند.");
        }

        $this->info('بررسی و حذف رزروهای پرداخت نشده تمام شد.');
    }
}
