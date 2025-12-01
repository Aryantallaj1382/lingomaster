<?php

namespace App\Models;

use App\Models\payment\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanDuration extends Model
{
    use HasFactory;
    protected $table = 'plan_durations';
    protected $guarded = [];
    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }
}
