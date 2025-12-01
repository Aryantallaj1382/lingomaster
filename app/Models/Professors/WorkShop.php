<?php

namespace App\Models\Professors;

use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Country;
use App\Models\Languages;
use App\Models\Like;
use App\Models\payment\Payment;
use App\Models\Rating;
use App\Models\shop\Comment;
use App\Models\shop\Product;
use App\Models\TeachingType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class WorkShop extends Model
{
    use HasFactory;
    protected $table = 'work_shops';
    protected $guarded = [];
    protected $casts = [
        'headline' => 'array',
    ];
    public function surveyResponse(): MorphOne
    {
        return $this->morphOne(SurveyResponse::class, 'surveyable');
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class,'professors_id');
    }
    public function getImageAttribute($value)
    {
        return $value ? api::api. $value : null;
    }
    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function ageGroup(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(LanguageLevel::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(LearningSubgoal::class);
    }



    public function language()
    {
        return $this->belongsTo(Languages::class);
    }


    public function books()
    {
        return $this->hasMany(GroupClassBook::class);
    }
    public function discount(): HasOne
    {
        return $this->hasOne(DiscountForWorkShop::class, 'workshop_id');
    }


    public function address(): BelongsTo
    {
        return $this->belongsTo(Offline::class, 'address_id');
    }

    public function teachingType(): BelongsTo
    {
        return $this->belongsTo(TeachingType::class);
    }





    public function isLikedByUser($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function reserve(): GroupClass|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(WorkShopReservation::class);
    }


    public function payment(): \Illuminate\Database\Eloquent\Relations\MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'ratable');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
    public function city(): BelongsTo
    {
        return $this->belongsTo(\App\Models\iran_regions\IranCity::class);
    }



}
