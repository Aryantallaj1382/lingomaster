<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('buyer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_id')->constrained()->onDelete('cascade'); // ارتباط با خریدار
            $table->string('title'); // نام آدرس (مثلاً: منزل، محل کار)
            $table->foreignId('country_id')->constrained()->onDelete('restrict'); // ارتباط با جدول کشورها
            $table->unsignedInteger('city_id')->nullable();
            $table->foreign('city_id')->references('id')->on('iran_cities')->onDelete('set null');
            $table->string('region')->nullable();     // منطقه
            $table->text('address');                  // آدرس کامل
            $table->string('plaque')->nullable();     // پلاک
            $table->string('unit')->nullable();       // واحد
            $table->string('postal_code')->nullable();// کد پستی
            $table->string('mobile')->nullable();     // شماره موبایل گیرنده
            $table->decimal('latitude', 10, 7)->nullable();   // عرض جغرافیایی
            $table->decimal('longitude', 10, 7)->nullable();  // طول جغرافیایی
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyer_addresses');
    }
};
