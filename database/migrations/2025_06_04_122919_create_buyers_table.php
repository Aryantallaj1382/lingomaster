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
        Schema::create('buyers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->unique(); // رابطه یک به یک با users
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_code')->unique();
            $table->string('email')->unique();
            $table->string('mobile')->unique();
            $table->string('referral_code')->nullable();
            $table->string('profile')->nullable(); // می‌تونه مسیر عکس پروفایل باشه
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buyers');
    }
};
