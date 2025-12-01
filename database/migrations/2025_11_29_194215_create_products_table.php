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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Using decimal with 2 scale for price
            $table->decimal('price', 12, 2);
            // immutable total stock at start
            $table->unsignedInteger('stock')->default(0);
            // dynamic counters
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('sold')->default(0);

            $table->timestamps();

            $table->index(['stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
