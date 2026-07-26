<?php

use App\Models\Consumable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data-only migration: copies consumable_restocks history into stock_movements
     * before that table is dropped in the following migration, so no audit history is lost.
     */
    public function up(): void
    {
        DB::table('consumable_restocks')->orderBy('id')->each(function (object $restock) {
            DB::table('stock_movements')->insert([
                'movable_type' => Consumable::class,
                'movable_id' => $restock->consumable_id,
                'type' => 'restock',
                'quantity_before' => null,
                'quantity_after' => null,
                'quantity_delta' => $restock->quantity_added,
                'unit_cost' => $restock->cost,
                'notes' => $restock->notes,
                'recorded_by' => $restock->restocked_by,
                'created_at' => $restock->created_at,
                'updated_at' => $restock->updated_at,
            ]);
        });
    }

    /**
     * Not reversible: the migrated rows can't be reliably distinguished back out
     * of stock_movements once other movements have been recorded alongside them.
     */
    public function down(): void
    {
        //
    }
};
