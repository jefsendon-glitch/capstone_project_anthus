<?php

use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderExpenseService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')->nullable()->unique()->constrained('purchase_orders')->nullOnDelete();
        });

        PurchaseOrder::whereNotIn('status', ['draft', 'cancelled'])->chunkById(100, function ($orders) {
            foreach ($orders as $order) {
                app(PurchaseOrderExpenseService::class)->sync($order);
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropUnique(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });
    }
};
