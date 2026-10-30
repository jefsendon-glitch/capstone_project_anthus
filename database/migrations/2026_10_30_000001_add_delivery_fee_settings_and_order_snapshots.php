<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->decimal('delivery_fee_per_gallon', 10, 2)->default(0);
        });

        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->decimal('delivery_fee_rate', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->unsignedInteger('delivery_gallon_count')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_fee_rate', 'delivery_fee', 'delivery_gallon_count']);
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('delivery_fee_per_gallon');
        });
    }
};
