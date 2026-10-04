<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // The abbreviation shown beside a quantity on receipts and stock
            // lists: kg, L, pc.
            $table->string('code', 12)->unique();

            // Whether a quantity in this unit may be fractional. Weighed goods
            // sell as 1.25 kg; discrete goods must not sell as 1.25 pc, and the
            // register rounds accordingly.
            $table->boolean('allows_fractional')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
