<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallon_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->enum('status', ['filled', 'empty', 'customer_owned', 'company_owned', 'returned', 'damaged', 'missing']);
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallon_stocks');
    }
};
