<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('water_production_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreignId('station_gallon_movement_id')->nullable()->unique()
                ->constrained('station_gallon_movements')->nullOnDelete();
            $table->string('container_name')->nullable();
            $table->string('container_size', 100)->nullable();
        });

        // Preserve existing fill activity as production history, using the original date.
        DB::table('station_gallon_movements as m')
            ->join('station_gallons as g', 'g.id', '=', 'm.station_gallon_id')
            ->where('m.action', 'fill')
            ->select('m.*', 'g.name as container_name', 'g.size as container_size')
            ->orderBy('m.id')->chunk(200, function ($movements) {
                foreach ($movements as $movement) {
                    DB::table('water_production_logs')->insert([
                        'product_id' => null,
                        'station_gallon_movement_id' => $movement->id,
                        'container_name' => $movement->container_name,
                        'container_size' => $movement->container_size,
                        'gallons_produced' => $movement->quantity,
                        'produced_by' => $movement->recorded_by,
                        'production_date' => substr($movement->created_at, 0, 10),
                        'notes' => $movement->notes,
                        'created_at' => $movement->created_at,
                        'updated_at' => $movement->updated_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('water_production_logs')->whereNull('product_id')->delete();
        Schema::table('water_production_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('station_gallon_movement_id');
            $table->dropColumn(['container_name', 'container_size']);
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
