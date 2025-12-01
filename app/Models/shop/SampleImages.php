<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SampleImages extends Model
{
    protected $table ='sample_images';
    use HasFactory;
    protected $fillable = ['path', 'imageable_id', 'imageable_type'];
    protected $hidden = ['imageable_id', 'imageable_type', 'deleted_at'];


    public function SampleImages(): MorphTo
    {
        return $this->morphTo('imagable', 'imageable_type', 'imageable_id');
    }
}
