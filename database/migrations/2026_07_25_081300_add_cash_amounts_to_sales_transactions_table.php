<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->decimal('tendered_amount', 10, 2)->nullable()->after('total_amount');
            $table->decimal('change_amount', 10, 2)->nullable()->after('tendered_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn(['tendered_amount', 'change_amount']);
        });
    }
};
