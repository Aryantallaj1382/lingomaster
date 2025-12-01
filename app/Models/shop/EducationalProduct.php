<?php
namespace App\Models\shop;

use App\Models\AgeGroup;
use App\Models\Languages;
use App\Models\Seller\Seller;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class EducationalProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'age_group_id',
        'language_id',
        'price',
        'off_price',
        'free_shipping',
        'today_shipping',
        'is_download',
        'discount_percentage',
        'discount_expiration',
    ];

    /**
     * رابطه با جدول گروه سنی
     */
    public function ageGroup()
    {
        return $this->belongsTo(AgeGroup::class);
    }

    /**
     * رابطه با جدول زبان
     */
    public function language()
    {
        return $this->belongsTo(Languages::class);
    }


    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function sellers(): BelongsToMany
    {
        return $this->belongsToMany(Seller::class, 'educational_product_seller');
    }
    protected function casts(): array
    {
        return [
            'price' => 'int',
            'is_download' => 'int',
            'off_price' => 'int',

        ];
    }
}
