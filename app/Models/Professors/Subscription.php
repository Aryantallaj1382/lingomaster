<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;
    protected $table ='subscriptions';

    protected $fillable = [
        'professors_id',
        'plan_id',
        'duration',
        'price',
    ];

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professors_id');
    }
}
