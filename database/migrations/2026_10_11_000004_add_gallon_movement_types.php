<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY type ENUM('initial', 'restock', 'adjustment_increase', 'adjustment_decrease', 'damage', 'missing', 'returned', 'sale_deduction', 'production', 'purchase_receive', 'fill', 'release') NOT NULL");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_movements MODIFY type ENUM('initial', 'restock', 'adjustment_increase', 'adjustment_decrease', 'damage', 'missing', 'returned', 'sale_deduction', 'production', 'purchase_receive') NOT NULL");
        }
    }
};
