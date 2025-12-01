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
        Schema::table('sellers', function (Blueprint $table) {
            $table->boolean('is_legal')->default(false);
            $table->string('national_card_file')->nullable();
            $table->string('business_license_file')->nullable();
            $table->boolean('is_verified')->default(false);
        });
        Schema::create('seller_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->foreignId('country_id')->constrained()->onDelete('restrict');
            $table->unsignedInteger('city_id')->nullable();
            $table->foreign('city_id')->references('id')->on('iran_cities')->onDelete('set null');
            $table->string('region')->nullable();
            $table->text('address');
            $table->string('plaque')->nullable();
            $table->string('unit')->nullable();      // واحد
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
        Schema::table('sellers', function (Blueprint $table) {
            //
        });
    }
};
