<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('payment_method');
            $table->string('payment_status')->default('unpaid'); // unpaid | paid | failed | refunded
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postcode');
            $table->string('country');
            $table->text('notes')->nullable();

            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('shipping')->default(0);
            $table->unsignedInteger('tax')->default(0);
            $table->unsignedInteger('total');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku');
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
