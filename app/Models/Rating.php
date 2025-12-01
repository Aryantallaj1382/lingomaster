<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ratable()
    {
        return $this->morphTo();
    }
    /**
     * ثبت یا بروزرسانی رای یک کاربر برای یک مدل مشخص
     *
     * @param string $ratableType  مدل (مثلا App\Models\Post)
     * @param int $ratableId       شناسه مدل
     * @param int $userId          شناسه کاربر
     * @param int|float $rating    امتیاز 0 تا 5
     * @return Rating
     * @throws \Exception اگر امتیاز معتبر نبود
     */
    public static function rate($ratableType, $ratableId, $userId, $rating)
    {
        // اعتبارسنجی امتیاز
        if (!is_numeric($rating) || $rating < 0 || $rating > 5) {
            throw new \Exception("امتیاز باید عددی بین 0 تا 5 باشد.");
        }

        // پاک کردن رای قبلی اگر وجود داشت
        self::where('ratable_type', $ratableType)
            ->where('ratable_id', $ratableId)
            ->where('user_id', $userId)
            ->delete();

        // ذخیره رای جدید
        return self::create([
            'ratable_type' => $ratableType,
            'ratable_id' => $ratableId,
            'user_id' => $userId,
            'rating' => $rating,
        ]);
    }


}
