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
        Schema::table('product_seller', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(0);
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('discounted_price')->nullable();
            $table->timestamp('discount_expire_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_seller', function (Blueprint $table) {
            //
        });
    }
};
