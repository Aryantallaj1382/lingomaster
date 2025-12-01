<?php

namespace App\Models\shop;

use App\Models\AgeGroup;
use App\Models\contacts\api;
use App\Models\Languages;
use App\Models\Like;
use App\Models\order\OrderItem;
use App\Models\Professors\BookLearning;
use App\Models\Professors\GroupClass;
use App\Models\Professors\LanguageLevel;
use App\Models\Professors\LearningSubgoal;
use App\Models\Rating;
use App\Models\Seller\Seller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';

    protected $guarded=[];
    protected $casts = ['tags' => 'array'];
    public function scopeTopOrders($query)
    {
        return $query->select(
            'products.id',
            'products.slug',
            'products.title',
            'products.image',
            DB::raw('COUNT(order_items.id) as orders_count')
        )
            ->leftJoin('order_items', 'products.id', '=', 'order_items.product_id')
            ->groupBy('products.id', 'products.slug', 'products.title', 'products.image')
            ->orderByDesc('orders_count');
    }
    public function scopeExceptHelpProducts($query)
    {
        return $query->where('category_id', '!=', 1);
    }

    public function bookLearnings()
    {
        return $this->hasMany(BookLearning::class, 'product_id');
    }
    // app/Models/Product.php

    public function scopeHelpProducts($query)
    {
        return $query->where('category_id', 1);
    }



    public function scopeHasDiscount($query)
    {
        return $query->whereHas('sellers', function ($q) {
            $q->whereNotNull('discounted_price')
                ->whereColumn('discounted_price', '<', 'price')
                ->where(function($query) {
                    $query->whereNull('discount_expire_at') // اگه نال بود اوکی
                    ->orWhere('discount_expire_at', '>', now()); // یا اگر مقدار داره از الان بزرگتر باشه
                });
        });
    }
    public function product_type(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function age_group(): BelongsTo
    {
        return $this->belongsTo(AgeGroup::class, 'age_group_id');
    }

    public function language(): BelongsTo
    {
    return $this->belongsTo(Languages::class, 'language_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subjects::class, 'subject_id');
    }

    public function accent1(): BelongsTo
    {
        return $this->belongsTo(Accent::class, 'accent_id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
    public function getFirstImageAttribute()
    {
        $image = $this->images->first()?->path ?? null;
        return $image ? api::api . $image : null;
    }

    public function SampleImages(): MorphMany
    {
        return $this->morphMany(SampleImages::class, 'imageable');
    }

    public function videos(): MorphMany
    {
        return $this->morphMany(Videos::class, 'videoable');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }
    public function isLikedByUser($userId): int
    {
        return $this->likes()->where('user_id', $userId)->exists() ? 1 : 0;
    }
    public function getIsLikedAttribute(): int
    {
        if (!auth()->check()) {
            return 0;
        }

        return $this->isLikedByUser(auth()->id());
    }

    public function sellers()
    {
        return $this->hasMany(ProductSeller::class, 'product_id');
    }


    protected function casts(): array
    {
        return [
            'price' => 'int',
            'is_download' => 'int',
            'weight' => 'int',
            'is_like' => 'int',
            'Volume_number' => 'int',
            'page_number' => 'int',
            'shabak_number' => 'int',
            'fipa_number' => 'int',
            'page-number' => 'int',
            'Time_to_print' => 'int',
            'Volume-number' => 'int',
        ];
    }


    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'ratable');
    }

    public function getUserRatingAttribute(): ?int
    {
        if (!auth()->check()) {
            return null;
        }

        return $this->ratings()->where('user_id', auth()->id())->value('rating');
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->ratings()->avg('rating'), 1) ?? 0;
    }

    public function getRatingsCountAttribute(): int
    {
        return $this->ratings()->count();
    }

    public function covers(): MorphMany
    {
        return $this->morphMany(Cover::class, 'coverable');
    }
    public function publication(): BelongsTo
    {
        return $this->belongsTo(Publication::class, 'publication_id');
    }
    public function goal(): BelongsTo
    {
        return $this->belongsTo(LearningSubgoal::class,'learning_subgoal_id');
    }

    public function language_level(): BelongsTo
    {
        return $this->belongsTo(LanguageLevel::class,'language_levels_id');
    }

    public function groupClasses()
    {
        return $this->belongsToMany(GroupClass::class, 'group_class_books', 'book_id', 'group_class_id');
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }




    // Product.php

    public function getDefaultPriceAttribute()
    {
        $seller = $this->sellers()->where('seller_id', 1)->first()
            ?? $this->sellers()->orderBy('discounted_price', 'asc')->first();

        return $seller?->price;
    }

    public function getDefaultDiscountedPriceAttribute()
    {
        $seller = $this->sellers()->where('seller_id', 1)->first()
            ?? $this->sellers()->orderBy('discounted_price', 'asc')->first();

        return $seller?->discounted_price;
    }

    public function getDefaultDiscountExpireAtAttribute()
    {
        $seller = $this->sellers()->where('seller_id', 1)->first()
            ?? $this->sellers()->orderBy('discounted_price', 'asc')->first();

        return $seller?->discount_expire_at;
    }

    public function getDefaultDiscountPercentageAttribute()
    {
        $seller = $this->sellers()->where('seller_id', 1)->first()
            ?? $this->sellers()->orderBy('discounted_price', 'asc')->first();

        if (!$seller || !$seller->price || !$seller->discounted_price) {
            return null;
        }

        $discount = (($seller->price - $seller->discounted_price) / $seller->price) * 100;
        return round($discount); // به صورت درصد گرد شده
    }

    // داخل مدل Product.php

    public function getSelectedSellerAttribute()
    {
        // فروشنده با seller_id = 1 رو پیدا کن
        $seller = $this->sellers()->where('seller_id', 1)->first();

        // اگر نبود، ارزان‌ترین فروشنده را بیار (بر اساس discounted_price)
        if (!$seller) {
            $seller = $this->sellers()->orderBy('discounted_price', 'asc')->first();
        }

        if (!$seller) {
            return null;
        }

        // بررسی انقضای تخفیف
        if ($seller->discounted_price && $seller->discount_expire_at && now()->gt($seller->discount_expire_at)) {
            $seller->discounted_price = $seller->price;
            $seller->discount_expire_at = null;
            $seller->save(); // ذخیره در دیتابیس
        }

        return [
            'id' => $seller->seller_id,
            'title' => $seller->seller->name ?? null,
            'price' => $seller->price,
            'discounted_price' => $seller->discounted_price,
            'discount_expire_at' => $seller->discount_expire_at,
            'discount_percentage' => $seller->discount_Percentage,
            'stock' => $seller->quantity ?? null,
        ];
    }

    public function getSelectedSellerBy($sellerId)
    {
        $seller = $this->sellers()->where('seller_id', $sellerId)->first();

        if (!$seller) {
            return null;
        }

        // بررسی انقضای تخفیف
        if ($seller->discounted_price && $seller->discount_expire_at && now()->gt($seller->discount_expire_at)) {
            $seller->discounted_price = $seller->price;
            $seller->discount_expire_at = null;
            $seller->save();
        }

        return [
            'id' => $seller->seller_id,
            'title' => $seller->seller->name ?? null,
            'price' => $seller->price,
            'discounted_price' => $seller->discounted_price,
            'discount_expire_at' => $seller->discount_expire_at,
            'discount_percentage' => $seller->discount_Percentage,
            'stock' => $seller->quantity ?? null,
        ];
    }

    public function scopeFilterByRequest($query, Request $request)
    {
        // دسته‌بندی (با اسلاگ)
        if ($request->route('slug')) {
            $slug = $request->route('slug');
            $query->whereHas('category', function ($q) use ($slug) {
                $q->where('slug', $slug);
            });
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('language')) {
            $query->where('language_id', $request->language);
        }

        if ($request->filled('learning_goals')) {
            $query->where('learning_subgoal_id', $request->learning_goals);
        }

        if ($request->filled('publication')) {
            $query->where('publication_id', $request->publication);
        }
        if ($request->filled('publication')) {
            $query->where('publication_id', $request->publication);
        }
        if ($request->filled('level')) {
            $query->where('language_levels_id', $request->level);
        }

        if ($request->filled('age_group')) {
            $query->where('age_group_id', $request->age_group);
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category)->orWhereRelation('category','slug','=', $request->category);
        }
//        if ($request->filled('category')) {
//            $query->whereRelation('category','slug','=', $request->category);
//        }
        if ($request->filled('Dimensions')) {

            if ($request->Dimensions == 1) {
                $query->where('dimensions', '=', 1);
            }

            if ($request->Dimensions == 2) {
                $query->where('dimensions', '>', 1);
            }
        }
        if ($request->filled('time_of_print')) {

            if ($request->time_of_print == 1) {
                $query->where('Time_to_print', '=', 1);
            }
            if ($request->time_of_print == 2) {
                $query->where('Time_to_print', '=', 2);
            }
            if ($request->time_of_print == 3) {
                $query->where('Time_to_print', '=', 3);
            }
            if ($request->time_of_print == 4) {
                $query->where('Time_to_print', '=', 4);
            }
            if ($request->time_of_print == 5) {
                $query->where('Time_to_print', '=', 5);
            }
            if ($request->time_of_print == 6) {
                $query->where('Time_to_print', '=', 6);
            }



        }


        if ($request->filled('is_used')) {
            $query->where('is_second_hand', $request->is_used === 'true' ? 1 : 0);
        }

        if ($request->filled('min_price') || $request->filled('max_price')) {
            $query->whereIn('id', function ($subQuery) use ($request) {
                $subQuery->select('product_id')
                    ->from('product_seller'); // ستون اصلی که باید بازگردانده شود

                // اعمال شرط برای min_price
                if ($request->filled('min_price')) {
                    $subQuery->whereRaw('COALESCE(discounted_price, price) >= ?', [$request->min_price]);
                }

                // اعمال شرط برای max_price
                if ($request->filled('max_price')) {
                    $subQuery->whereRaw('COALESCE(discounted_price, price) <= ?', [$request->max_price]);
                }
            });
        }

        if ($request->filled('discounted') && $request->discounted == 'true') {
            $query->whereIn('id', function ($subQuery) {
                $subQuery->select('product_id')
                    ->from('product_seller')
                    ->whereNotNull('discounted_price')
                    ->whereColumn('discounted_price', '<', 'price');
            });
        }

        return $query;
    }


    public function scopeSortByRequest($query, Request $request)
    {
        if ($request->filled('sort')) {
            switch ($request->sort) {
                case 'expensive':
                    return $query->with(['sellers'])
                        ->selectRaw('products.*, (select min(price) from product_seller where products.id = product_seller.product_id) as sellers_min_price')
                        ->orderBy('sellers_min_price', 'desc');

                case 'cheap':
                    return $query->with(['sellers'])
                        ->selectRaw('products.*, (select min(price) from product_seller where products.id = product_seller.product_id) as sellers_min_price')
                        ->orderBy('sellers_min_price', 'asc');
                case 'bestselling':
                    return $query->orderBy('sales_count', 'desc');
                case 'view':
                    return $query->orderBy('view', 'desc');

//                case 'discount':
//                    return $query
//                        ->withMax(['sellers as discount_percent' => function ($q) {
//                            // اگر discounted_price مقدار داشت و کوچک‌تر از price بود،
//                            // درصد تخفیف را حساب می‌کنیم؛ در غیر این صورت صفر.
//                            $q->select(DB::raw("
//            IF(
//                discounted_price IS NOT NULL
//                AND discounted_price < price,
//                ROUND( ( (price - discounted_price) / price ) * 100 , 2 ),
//                0
//            )
//        "));
//                        }])
//                        // ابتدا آیتم‌های بدون تخفیف به ته لیست بروند، بعد بر اساس درصد تخفیف نزولی
//                        ->orderByRaw('discount_percent = 0')   // false(0) → اولِ لیست، true(1) → آخر
//                        ->orderBy('discount_percent', 'desc');
//                        // ابتدا آیتم‌های بدون تخفیف به ته لیست بروند، بعد بر اساس درصد تخفیف نزولی
//                        ->orderByRaw('discount_percent = 0')   // false(0) → اولِ لیست، true(1) → آخر
//                        ->orderBy('discount_percent', 'desc');
//                                ->whereColumn('product_id', 'products.id')
//                        ])
//                        ->orderByRaw('discount_percent = 0') // no discount → end
//                        ->orderBy('discount_percent', 'desc');

                case 'newest':
                default:
                    return $query->latest();
            }
        }

        return $query->latest();
    }

}
