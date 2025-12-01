<?php

namespace App\Models\shop;

use App\Models\Like;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AudioComment extends Model
{
    protected $fillable = ['product_id','audio_path'];

    public function comment()
    {
        return $this->morphOne(Comment::class, 'commentable');
    }
    public function product() : BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    // رابطه برای دیس‌لایک‌ها
    public function dislikes(): MorphMany
    {
        return $this->morphMany(Dislike::class, 'dislikable');
    }
}
