<?php

namespace App\Models\Professors;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table= 'notifications';


    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class,'professors_id');
    }

}
