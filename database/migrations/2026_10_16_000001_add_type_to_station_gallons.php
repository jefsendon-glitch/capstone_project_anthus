<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('station_gallons', function (Blueprint $table) {
            $table->string('type', 100)->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('station_gallons', fn (Blueprint $table) => $table->dropColumn('type'));
    }
};
