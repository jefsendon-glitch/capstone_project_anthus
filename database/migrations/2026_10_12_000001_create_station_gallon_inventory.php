<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('station_gallons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('size', 100);
            foreach (['empty', 'filled', 'borrowed', 'damaged', 'missing', 'returned'] as $status) {
                $table->unsignedInteger($status)->default(0);
            }
            $table->timestamps();
        });
        Schema::create('station_gallon_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_gallon_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->unique(['station_gallon_id', 'customer_id']);
        });
        Schema::create('station_gallon_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_gallon_id')->constrained()->restrictOnDelete();
            $table->string('action', 30);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->unsignedInteger('quantity');
            $table->foreignId('customer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('station_gallon_movements');
        Schema::dropIfExists('station_gallon_loans');
        Schema::dropIfExists('station_gallons');
    }
};
