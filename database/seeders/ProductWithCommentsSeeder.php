<?php
namespace Database\Seeders;

use App\Models\shop\Product;
use App\Models\shop\TextComment;
use Illuminate\Database\Seeder;

class ProductWithCommentsSeeder extends Seeder
{
    public function run()
    {
        // ایجاد محصول همراه با ۵ کامنت متنی
        Product::factory()
            ->has(TextComment::factory()->count(5)) // مرتبط‌سازی با ۵ کامنت
            ->create();
    }
}
