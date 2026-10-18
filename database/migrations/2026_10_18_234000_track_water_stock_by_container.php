<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('round_stock')->default(0);
            $table->unsignedInteger('slim_stock')->default(0);
        });
        // Existing totals have no reliable container breakdown. Preserve them until
        // Admin allocates the actual Round/Slim quantities in Edit Product.
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['round_stock', 'slim_stock']));
    }
};
