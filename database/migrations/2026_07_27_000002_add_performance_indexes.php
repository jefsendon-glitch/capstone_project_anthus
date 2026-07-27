<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->index('status');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->index('category');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropIndex(['is_active']);
        });
    }
};
