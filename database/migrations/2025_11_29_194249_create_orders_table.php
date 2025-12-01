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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hold_id')->constrained('holds')->cascadeOnDelete()->unique();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->unsignedInteger('qty');
            $table->decimal('total_price', 14, 2);
            $table->enum('status', ['pending_payment','paid','cancelled'])->default('pending_payment');

            // optional provider payment id/reference
            $table->string('payment_reference')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
