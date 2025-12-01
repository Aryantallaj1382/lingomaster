<?php

namespace App\Models;

use App\Models\Financial\Payment;
use App\Models\Professors\Professor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfessorPlanSubscription extends Model
{
    use HasFactory;
    protected $table = 'professor_plan_subscriptions';
    protected $guarded = [];

    public function language()
    {
        return $this->belongsTo(Languages::class);
    }
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
        // اگر کلید خارجی شما 'plan_id' نیست، آن را اصلاح کنید
    }
    public function profesor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
        // اگر کلید خارجی شما 'plan_id' نیست، آن را اصلاح کنید
    }
    public function duration()
    {
        return $this->belongsTo(PlanDuration::class, 'plan_duration_id');
        // اگر کلید خارجی شما 'plan_id' نیست، آن را اصلاح کنید
    }
    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }
}
