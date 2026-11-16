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
        Schema::table('product_specifications', function (Blueprint $table): void {
            $table->json('options')->nullable()->after('measurement_unit');
            $table->boolean('allow_custom')->default(false)->after('options');
        });

        Schema::create('product_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('specifications');
            $table->decimal('selling_price', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['product_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::table('product_specifications', function (Blueprint $table): void {
            $table->dropColumn(['options', 'allow_custom']);
        });
    }
};
