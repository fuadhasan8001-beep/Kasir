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
        Schema::create('custom_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->uuid('request_key')->unique();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('customer_name');
            $table->string('customer_phone', 40)->nullable();
            $table->date('order_date');
            $table->json('specifications')->nullable();
            $table->string('diameter', 80)->nullable();
            $table->string('shape', 100)->nullable();
            $table->decimal('quantity', 15, 3);
            $table->string('stock_basis', 16);
            $table->string('stock_unit', 32);
            $table->decimal('estimated_weight', 15, 3)->nullable();
            $table->decimal('actual_weight', 15, 3)->nullable();
            $table->string('price_basis', 16);
            $table->decimal('agreed_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->date('estimated_completion_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('custom_order_id')->nullable()->after('sale_id')->constrained()->nullOnDelete();
            $table->unique(['custom_order_id', 'movement_type']);
        });

        Schema::create('custom_order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->uuid('request_key')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('payment_method', 20);
            $table->dateTime('paid_at');
            $table->timestamps();
            $table->index(['custom_order_id', 'paid_at']);
        });

        Schema::create('custom_order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['custom_order_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_order_status_histories');
        Schema::dropIfExists('custom_order_payments');
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique(['custom_order_id', 'movement_type']);
            $table->dropConstrainedForeignId('custom_order_id');
        });
        Schema::dropIfExists('custom_orders');
    }
};
