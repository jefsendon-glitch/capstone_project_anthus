<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('supplier')->constrained('suppliers')->nullOnDelete();
        });

        $names = DB::table('consumables')->whereNotNull('supplier')->where('supplier', '!=', '')->distinct()->pluck('supplier');

        foreach ($names as $name) {
            $supplierId = DB::table('suppliers')->insertGetId([
                'name' => $name,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('consumables')->where('supplier', $name)->update(['supplier_id' => $supplierId]);
        }
    }

    public function down(): void
    {
        Schema::table('consumables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
