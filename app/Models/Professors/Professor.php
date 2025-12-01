<?php

namespace App\Models\Professors;

use App\Models\AgeGroup;
use App\Models\Country;
use App\Models\GeographicRegion;
use App\Models\Languages;
use App\Models\Like;
use App\Models\payment\Payment;
use App\Models\PlacementTest;
use App\Models\ProfessorPlanSubscription;
use App\Models\Rating;
use App\Models\shop\Accent;
use App\Models\shop\Comment;
use App\Models\shop\Image;
use App\Models\shop\Videos;
use App\Models\SurveyResponse;
use App\Models\TeachingType;
use App\Models\TrialClass;
use App\Models\User;
use App\Observers\ProfessorObserver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;


class Professor extends Model
{
    use HasFactory;
    protected $table = 'professors';
    protected $guarded=[];
    protected $casts = [
        'is_visible' => 'boolean',
        'is_paid' => 'boolean',
    ];

    public function scopePriorityOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN priority = 0 THEN 1 ELSE 0 END') // صفرها برن آخر
            ->orderByRaw('CAST(priority AS UNSIGNED) ASC'); // بقیه به ترتیب عددی
    }


    public function activePlanSubscription($languageId) {
        return $this->hasOne(ProfessorPlanSubscription::class)
            ->where('language_id', $languageId)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }
    protected static function booted(): void
    {
        static::observe(ProfessorObserver::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('status', 'approved')
            ->where('is_visible', true) // 👈 اضافه شد
            ->whereHas('languages')
            ->whereHas('ageGroupLanguageLevels')
            ->whereHas('ageGroups')
            ->whereHas('learningGoals')
            ->whereHas('ClassPrice', function ($q) {
                $q->where('class_type', 'private');
            });
    }

    public function getExperienceAttribute()
    {
        $createdAt = $this->created_at;
        $now = \Carbon\Carbon::now();

        $diffInMonths = $createdAt->diffInMonths($now, false); // مقدار واقعی (مثلاً 1.7)
        $diffInDays = $createdAt->diffInDays($now);

        $roundedMonths = round($createdAt->floatDiffInMonths($now)); // برای رند کردن ماه‌ها

        return $roundedMonths < 1
            ? round($diffInDays) . ' روز'
            : $roundedMonths . ' ماه';
    }


    public function getActivePlanFeatures($languageId)
    {
        $subscription = $this->activePlanSubscription($languageId)->with('plan')->first();
        return $subscription?->plan?->features ?? [];
    }

    public function canUseFeature($featureKey,$languageId, $expected = true)
    {
        $features = $this->getActivePlanFeatures($languageId);

        if (is_string($features)) {
            $features = json_decode($features, true) ?? [];
        }

        if (!is_array($features)) {
            return false;
        }

        if (!array_key_exists($featureKey, $features)) {
            return false;
        }

        $featureValue = $features[$featureKey];

        // اگر مقدار انتظار عدد یا رشته است، فقط با مقایسه مقدار چک کن
        if (is_bool($expected)) {
            return $featureValue === $expected;
        }

        // در غیر این صورت، مقایسه رشته‌ای (حساس به حروف و فاصله)
        return trim($featureValue) === trim($expected);
    }


///برای استفاده در کنترلر

//
//if (!$professor->canUseFeature('وبینار')) {
//return response()->json([
//'message' => 'شما مجاز به برگزاری وبینار نیستید. برای استفاده از این ویژگی، پلن VIP را تهیه کنید.'
//], 403);
//}






    public function surveyResponse(): MorphOne
    {
        return $this->morphOne(SurveyResponse::class, 'surveyable');
    }
    public function teachingTypes(): BelongsToMany
    {
        return $this->belongsToMany(TeachingType::class, 'professor_teaching_type',
            'professor_id', 'teaching_type_id')->withPivot('language_id');
    }


    public function teachingTimes(): BelongsToMany
    {
        return $this->belongsToMany(TeachingTime::class, 'professor_teaching_time', 'professor_id', 'teaching_time_id');
    }


    public function genders(): BelongsToMany
    {
        return $this->belongsToMany(Gender::class, 'professor_gender',
            'professor_id', 'gender_id')->withPivot('language_id');
    }


    public function ageGroups(): BelongsToMany
    {
        return $this->belongsToMany(AgeGroup::class, 'professor_age_group',
            'professor_id', 'age_group_id')->withPivot('language_id');
    }

    public function languageLevels(): BelongsToMany
    {
        return $this->belongsToMany(
            LanguageLevel::class,
            'professor_language_level',
            'professor_id',
            'language_level_id'
        )->withPivot('language_id');
    }

    public function languages(): BelongsToMany
    {
        return $this->belongsToMany(Languages::class, 'professor_languages', 'professor_id', 'language_id');
    }

    public function profile1(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('type', 'profile');
    }

    public function cover(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('type', 'cover');
    }
    public function school_video(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('type', 'school_video');
    }

    public function about_video(): MorphMany
    {
        return $this->morphMany(Videos::class, 'videoable')->where('type', 'about_video');
    }

    public function teaching_example_video(): MorphMany
    {
        return $this->morphMany(Videos::class, 'videoable')->where('type', 'teaching_example_video');
    }


    public function home_video(): MorphMany
    {
        return $this->morphMany(Videos::class, 'videoable')->where('type', 'home_video');
    }
    public function videos(): MorphMany
    {
        return $this->morphMany(Videos::class, 'videoable')->where('type', 'videos');
    }
    public function accent():HasMany  // در دیتابیس چیزی نساختم هنوز
    {
        return $this->hasMany(Accent::class, 'accent_id');
    }





    public function tests(): HasMany
    {
        return $this->hasMany(ProfessorTest::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProfessorTranslation::class);
    }



    public function skills(): HasMany
    {
        return $this->hasMany(ProfessorSkill::class);
    }
    public function online(): HasMany
    {
        return $this->hasMany(Online::class);
    }
    public function offline(): HasMany
    {
        return $this->hasMany(Offline::class);
    }
    public function teacherTimeSlots(): HasMany
    {
        return $this->hasMany(TeacherTimeSlot::class, 'teacher_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'student_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // رابطه با استان
    public function province(): BelongsTo
    {
        return $this->belongsTo(\App\Models\iran_regions\IranProvince::class);
    }

    // رابطه با شهر
    public function city(): BelongsTo
    {
        return $this->belongsTo(\App\Models\iran_regions\IranCity::class);
    }
    public function trial(): HasMany
    {
        return $this->hasMany(TrialClass::class);
    }
    public function placementTests(): HasMany
    {
        return $this->hasMany(PlacementTest::class);
    }


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gender_P(): BelongsTo
    {
        return $this->belongsTo(Gender::class , 'professors_gender');
    }
    public function addresses(): HasMany
    {
        return $this->hasMany(ProfessorAddress::class);
    }
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function translations1(): HasMany
    {
        return $this->hasMany(ProfessorTranslation::class, 'professors_id');
    }

    public function authors(): HasMany
    {
        return $this->hasMany(ProfessorAuthor::class, 'professors_id');
    }

    public function honors(): HasMany
    {
        return $this->hasMany(ProfessorHonor::class, 'professors_id');
    }

    public function blogs(): HasMany
    {
        return $this->hasMany(ProfessorBlog::class, 'professors_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(ProfessorCertificate::class, 'professors_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ProfessorRecord::class, 'professors_id');
    }

    public function education(): HasMany
    {
        return $this->hasMany(ProfessorEducation::class, 'professors_id');
    }



    public function nearestClasses()
    {
        return $this->hasMany(NearestClass::class);
    }

    // تعریف رابطه با زبان‌ها از طریق کلاس‌های نزدیک
    public function nearestClassLanguages()
    {
        return $this->belongsToMany(Languages::class, 'professor_nearest_class')
            ->withPivot('nearest_class_id', 'type')
            ->withTimestamps();
    }
    public function aboutTexts()
    {
        return $this->hasMany(ProfessorAboutText::class);
    }
    public function ageGroupLanguageLevels()
    {
        return $this->hasMany(AgeGroupLanguageLevel::class);
    }

    public function ageGroupTitles()
    {
        return $this->hasManyThrough(
            AgeGroup::class,            // مدل نهایی (AgeGroup)
            AgeGroupLanguageLevel::class, // مدل واسط (AgeGroupLanguageLevel)
            'professor_id',            // کلید خارجی در AgeGroupLanguageLevel که به Professor متصل است
            'id',                      // کلید اصلی در AgeGroup
            'id',                      // کلید اصلی در Professor
            'age_group_id'             // کلید خارجی در AgeGroupLanguageLevel که به AgeGroup متصل است
        )->pluck('title'); // فقط `title` را دریافت کن
    }
    public function classTypes()
    {
        return $this->hasMany(ClassType::class, 'professor_id');
    }
    public function accents(): BelongsToMany
    {
        return $this->belongsToMany(Accent::class, 'accents_professor', 'professor_id', 'accent_id');
    }

    public function learningGoals(): HasMany
    {
        return $this->hasMany(ProfessorLearningGoal::class);
    }

    public function groupClasses(): HasMany
    {
        return $this->hasMany(GroupClass::class);
    }
    public function full_name(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function discount(): HasMany|Professor
    {
        return $this->hasMany(DiscountClass::class, 'professor_id');
    }

    public function ClassPrice()
    {
        return $this->hasMany(ClassPrice::class);
    }

    public function price()
    {
        return $this->hasMany(ClassPrice::class);
    }



    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }


    public function isLikedByUser($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function reserve(): GroupClass|\Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(GroupClassReservation::class);
    }


    public function payment(): MorphOne
    {
        return $this->morphOne(Payment::class, 'payable');
    }
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'ratable');
    }


}

