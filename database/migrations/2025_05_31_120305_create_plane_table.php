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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // عادی، ویژه، VIP
            $table->text('description')->nullable(); // توضیح مختصر
            $table->json('features'); // ویژگی‌ها
            $table->timestamps();
        });
        Schema::create('plan_durations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->integer('months'); // 1, 3, 6, 12
            $table->decimal('price', 12, 0); // تومان
            $table->tinyInteger('discount_percent')->default(0); // 0، 10، 15، 25
            $table->timestamps();
        });
        Schema::create('professor_plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained();
            $table->foreignId('plan_duration_id')->constrained();
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->timestamps();
        });



    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plane');
    }
};
