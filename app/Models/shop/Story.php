<?php

namespace App\Models\shop;

use App\Models\contacts\api;
use App\Models\Like;
use App\Models\Professors\Professor;
use App\Models\Seller\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Story extends Model
{
    use HasFactory;
    protected $guarded=[];
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }
    public function comment(): MorphOne
    {
        return $this->morphOne(Comment::class, 'commentable');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function getCoverAttribute($value)
    {
        if ($value) {
            return api::api . $value;
        }

        if ($this->user && $this->user->profile_image) {
            return api::api . $this->user->profile_image;
        }

        return null;
    }

    public function getVideoAttribute($value)
    {
        return $value ?api::api . $value : null;
    }
}
