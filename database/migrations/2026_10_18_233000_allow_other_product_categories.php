<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->change();
        });
    }

    public function down(): void
    {
        // Keep the expanded column so existing non-water products remain valid.
    }
};
