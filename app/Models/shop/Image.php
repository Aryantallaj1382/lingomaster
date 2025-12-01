<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{

    protected $fillable = ['path', 'imageable_id', 'imageable_type'];
    protected $hidden = ['imageable_id', 'imageable_type', 'deleted_at'];


    public function imagable(): MorphTo
    {
        return $this->morphTo('imagable', 'imageable_type', 'imageable_id');
    }





}
