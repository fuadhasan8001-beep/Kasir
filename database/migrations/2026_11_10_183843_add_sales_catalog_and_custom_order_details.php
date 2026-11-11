<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('image_path')->nullable()->after('description');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->string('customer_name')->nullable()->after('user_id');
            $table->foreignId('custom_order_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->unique('custom_order_id');
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->string('pricing_basis', 16)->default('unit')->after('unit_name');
        });

        Schema::table('custom_order_payments', function (Blueprint $table): void {
            $table->decimal('amount_received', 15, 2)->default(0)->after('amount');
            $table->decimal('change_amount', 15, 2)->default(0)->after('amount_received');
        });

        Schema::create('custom_order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('custom_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('quantity', 15, 3);
            $table->string('stock_basis', 16);
            $table->string('stock_unit', 32);
            $table->decimal('estimated_weight', 15, 3)->nullable();
            $table->decimal('actual_weight', 15, 3)->nullable();
            $table->string('price_basis', 16);
            $table->decimal('agreed_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->default(0);
            $table->json('specifications')->nullable();
            $table->string('diameter', 80)->nullable();
            $table->string('shape', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['custom_order_id', 'product_id']);
        });

        DB::table('custom_order_items')->insertUsing(
            [
                'custom_order_id', 'product_id', 'product_name', 'quantity', 'stock_basis', 'stock_unit',
                'estimated_weight', 'actual_weight', 'price_basis', 'agreed_price', 'total_price',
                'specifications', 'diameter', 'shape', 'notes', 'created_at', 'updated_at',
            ],
            DB::table('custom_orders')->select([
                'id', 'product_id', 'product_name', 'quantity', 'stock_basis', 'stock_unit',
                'estimated_weight', 'actual_weight', 'price_basis', 'agreed_price', 'total_price',
                'specifications', 'diameter', 'shape', 'notes', 'created_at', 'updated_at',
            ]),
        );

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropUnique('stock_movements_custom_order_id_movement_type_unique');
            $table->foreignId('custom_order_item_id')->nullable()->after('custom_order_id')->constrained()->nullOnDelete();
        });

        foreach (DB::table('custom_orders')->select('id')->get() as $order) {
            $itemId = DB::table('custom_order_items')->where('custom_order_id', $order->id)->value('id');

            if ($itemId !== null) {
                DB::table('stock_movements')
                    ->where('custom_order_id', $order->id)
                    ->whereIn('movement_type', ['custom_order_ready', 'custom_order_delivery'])
                    ->update(['custom_order_item_id' => $itemId]);
            }
        }

        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->unique(['custom_order_item_id', 'movement_type']);
            $table->index(['custom_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropUnique(['custom_order_item_id', 'movement_type']);
            $table->dropIndex(['custom_order_id', 'created_at']);
            $table->dropConstrainedForeignId('custom_order_item_id');
        });

        Schema::dropIfExists('custom_order_items');

        Schema::table('custom_order_payments', function (Blueprint $table): void {
            $table->dropColumn(['amount_received', 'change_amount']);
        });

        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropColumn('pricing_basis');
        });

        Schema::table('sales', function (Blueprint $table): void {
            $table->dropUnique(['custom_order_id']);
            $table->dropConstrainedForeignId('custom_order_id');
            $table->dropColumn('customer_name');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('image_path');
        });
    }
};
