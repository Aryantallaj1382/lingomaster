<?php
namespace App\Models\shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Cover extends Model
{
    protected $fillable = ['path', 'coverable_id', 'coverable_type'];
    protected $hidden = ['coverable_id', 'coverable_type', 'deleted_at'];


    public function coverable(): MorphTo
    {
        return $this->morphTo('coverable', 'coverable_type', 'coverable_id');
    }

}

