<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_receipt_references', function (Blueprint $table) {
            $table->string('reference_code', 40)->primary();
            $table->timestamp('created_at');
        });
        DB::table('stock_movements')->where('type', 'purchase_receive')->where('reference_code', 'like', 'PR-%')
            ->orderBy('id')->chunkById(500, function ($movements) {
                foreach ($movements as $movement) {
                    DB::table('stock_receipt_references')->insertOrIgnore([
                        'reference_code' => $movement->reference_code, 'created_at' => $movement->created_at,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_receipt_references');
    }
};
