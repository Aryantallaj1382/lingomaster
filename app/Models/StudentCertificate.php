<?php

namespace App\Models;

use App\Models\Financial\Payment;
use App\Models\iran_regions\IranCity;
use Illuminate\Database\Eloquent\Model;

class StudentCertificate extends Model
{
    protected $guarded = [];

    // رابطه مورفی با کلاس‌های مختلف (PrivateClass, Webinar, Workshop, ...)
    public function certificatable()
    {
        return $this->morphTo();
    }

    // رابطه با دانش‌آموز (کاربر)
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    // رابطه با شهر
    public function city()
    {
        return $this->belongsTo(IranCity::class, 'city_id');
    }
    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }
}
