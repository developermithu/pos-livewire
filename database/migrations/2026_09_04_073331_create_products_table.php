<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();

            // The code staff type and scanners read. SKU is the internal
            // identifier and is always present; barcode is the manufacturer's
            // and is not, so it is nullable but still indexed for lookup speed
            // at the register.
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->index();

            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();

            $table->text('description')->nullable();

            // Money is an integer of minor units plus a currency code, never a
            // float — see App\Support\Money. One currency column per row, so a
            // product's cost and price cannot drift into different currencies.
            $table->unsignedBigInteger('cost_price_cents')->default(0);
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->char('currency', 3)->default('USD');

            // The on-hand level at which this product is reported for reorder.
            // Stock itself is not a column here: it is the append-only ledger
            // that lands in phase 5.
            $table->unsignedInteger('reorder_point')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
