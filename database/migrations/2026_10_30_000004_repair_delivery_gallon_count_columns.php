<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['delivery_orders', 'sales_transactions'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'delivery_gallon_count')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedInteger('delivery_gallon_count')->default(0);
                });
            }
        }
    }

    public function down(): void
    {
        // Keep the repaired data if this migration is rolled back.
    }
};
