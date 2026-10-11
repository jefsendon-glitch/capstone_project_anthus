<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->string('reference_code', 40)->nullable()->index()->after('id');
            $table->foreignId('customer_id')->nullable()->after('recorded_by')->constrained('users')->nullOnDelete();
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropForeign(['customer_id']);
            $table->dropIndex(['customer_id', 'created_at']);
            $table->dropIndex(['reference_code']);
            $table->dropColumn(['reference_code', 'customer_id']);
        });
    }
};
