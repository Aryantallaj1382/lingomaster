<?php
namespace App\Models;

use App\Models\shop\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ProductView extends Model
{
    protected $fillable = ['user_id', 'product_id'];

    public static function logView($productId)
    {
        $userId = Auth::id();

        $view = self::updateOrCreate(
            ['user_id' => $userId, 'product_id' => $productId],
            ['updated_at' => now()]
        );

        $extraViews = self::where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->skip(6)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($extraViews->isNotEmpty()) {
            self::destroy($extraViews);
        }

        return $view;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
