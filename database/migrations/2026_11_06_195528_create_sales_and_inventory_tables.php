<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('inventory_type', 20)->default('finished');
            $table->decimal('quantity', 15, 3)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'inventory_type']);
        });

        DB::table('inventory_levels')->insertUsing(
            ['product_id', 'inventory_type', 'quantity', 'created_at', 'updated_at'],
            DB::table('products')->selectRaw("id, 'finished', 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP"),
        );

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->uuid('request_key')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->string('payment_method', 20);
            $table->decimal('amount_received', 15, 2);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->string('payment_status', 20)->default('paid');
            $table->timestamps();
            $table->index(['created_at', 'payment_status']);
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('unit_name', 32);
            $table->decimal('quantity', 15, 3);
            $table->decimal('actual_weight', 15, 3)->nullable();
            $table->json('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('unit_cost', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
            $table->index(['sale_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->string('movement_type', 20);
            $table->decimal('quantity', 15, 3);
            $table->decimal('balance_after', 15, 3);
            $table->string('inventory_type', 20)->default('finished');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'created_at']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('category', 80);
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->timestamps();
            $table->index(['expense_date', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('inventory_levels');
    }
};
