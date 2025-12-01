<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('trial_classes', function (Blueprint $table) {
            $table->id(); // ID اصلی جدول
            $table->unsignedBigInteger('professor_id'); // ID استاد
            $table->unsignedBigInteger('language_id'); // ID زبان
            $table->boolean('is_trial')->default(false); // آیا کلاس آزمایشی برگزار می‌کند؟
            $table->enum('discount', ['free', '50_percent', 'no_discount'])->default('no_discount'); // وضعیت قیمت
            $table->timestamps();

            // روابط خارجی
            $table->foreign('professor_id')->references('id')->on('professors')->onDelete('cascade');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trial_classes');
    }
};
