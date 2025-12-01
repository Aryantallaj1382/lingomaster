<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tools extends Model
{
    use HasFactory;
    protected $fillable=
        [
          'title','body','price','off_price','language_id','age_group_id'
        ];

    public function age_group(): BelongsTo
    {
        return $this->belongsTo(Tools::class, 'age_group_id');
    }
    public function language(): BelongsTo
    {
        return $this->belongsTo(Tools::class, 'language_id');
    }
}
