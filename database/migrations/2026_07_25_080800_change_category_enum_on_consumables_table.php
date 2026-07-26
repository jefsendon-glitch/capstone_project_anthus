<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replaces the generic filter/chemical/packaging/equipment_part categories with the
     * 7 named Supply Inventory categories. Two-pass remap: a coarse old->new mapping,
     * then a name-based override pass that catches rows the coarse mapping gets wrong
     * (e.g. "Bottle Caps" was categorized as the generic "packaging"). The category
     * column is a plain string (not a DB-level enum) so this is just data movement,
     * portable across MySQL and SQLite.
     */
    public function up(): void
    {
        $baseMap = [
            'filter' => 'water_filters',
            'chemical' => 'cleaning_supplies',
            'packaging' => 'plastic_bags',
            'equipment_part' => 'uv_lamps',
        ];

        foreach ($baseMap as $old => $new) {
            DB::table('consumables')->where('category', $old)->update(['category' => $new]);
        }

        $nameOverrides = [
            'cap' => 'bottle_caps',
            'seal' => 'bottle_seals',
            'label' => 'labels',
            'bag' => 'plastic_bags',
            'filter' => 'water_filters',
            'uv' => 'uv_lamps',
            'lamp' => 'uv_lamps',
            'clean' => 'cleaning_supplies',
        ];

        foreach ($nameOverrides as $needle => $new) {
            DB::table('consumables')->where('name', 'like', "%{$needle}%")->update(['category' => $new]);
        }
    }

    public function down(): void
    {
        //
    }
};
