<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            foreach (DB::table('products')->where('category', 'mineral')->get(['id', 'name']) as $product) {
                $name = str_ireplace('mineral', 'Alkaline', $product->name);
                DB::table('products')->where('id', $product->id)->update([
                    'name' => $name === $product->name ? 'Alkaline Water' : $name,
                    'category' => 'alkaline', 'requires_container_choice' => true,
                ]);
            }
            DB::table('products')->where('category', 'alkaline')->update(['requires_container_choice' => true]);
        });
    }

    public function down(): void
    {
        // Original Alkaline products cannot be distinguished from converted products.
        // Preserve the catalog and historical transaction snapshots on rollback.
    }
};
