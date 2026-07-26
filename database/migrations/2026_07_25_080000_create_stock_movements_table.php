<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->morphs('movable');
            $table->enum('type', [
                'initial', 'restock', 'adjustment_increase', 'adjustment_decrease',
                'damage', 'missing', 'returned', 'sale_deduction', 'production', 'purchase_receive',
            ]);
            $table->decimal('quantity_before', 12, 2)->nullable();
            $table->decimal('quantity_after', 12, 2)->nullable();
            $table->decimal('quantity_delta', 12, 2);
            $table->decimal('unit_cost', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
