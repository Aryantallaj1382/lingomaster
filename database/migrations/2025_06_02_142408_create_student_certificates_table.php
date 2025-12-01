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
        Schema::create('student_certificates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('certificatable_id');
            $table->string('certificatable_type');
            $table->unsignedBigInteger('student_id');
            $table->foreign('student_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('name_fa');
            $table->string('name_en');
            $table->string('address')->nullable();
            $table->unsignedInteger('city_id')->nullable();
            $table->foreign('city_id')->references('id')->on('iran_cities')->onDelete('set null');
            $table->string('tracking_code')->nullable();
            $table->string('postal_code')->nullable();
            $table->enum('delivery_type', ['online', 'physical']);
            $table->string('file_path')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('payment_status', ['paid', 'unpaid'])->default('unpaid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_certificates');
    }
};
