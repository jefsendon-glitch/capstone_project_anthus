<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->boolean('requires_container_choice')->default(false));
        DB::table('products')->whereIn('category', ['purified', 'distilled', 'mineral'])->update(['requires_container_choice' => true]);
        foreach (['delivery_orders', 'delivery_order_items', 'sales_transactions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->string('container_type', 10)->nullable());
        }
    }

    public function down(): void
    {
        foreach (['delivery_orders', 'delivery_order_items', 'sales_transactions'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn('container_type'));
        }
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('requires_container_choice'));
    }
};
