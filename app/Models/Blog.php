<?php

namespace App\Models;

use App\Models\contacts\api;
use App\Models\shop\Comment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Blog extends Model
{
    use HasFactory;

    protected $table = 'blog';

    protected $guarded = [];

    protected $casts = [
        'tags' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Blog.php
    public function getImageAttribute($value)
    {
        return $value ?api::api . $value : null;
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
    public function isLikedByUser($userId): int
    {
        return $this->likes()->where('user_id', $userId)->exists() ? 1 : 0;
    }

}
