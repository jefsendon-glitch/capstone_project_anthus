<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_name');
            $table->enum('category', ['filter', 'pump', 'uv_lamp', 'ozone', 'ro_membrane', 'other']);
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_due_date');
            $table->enum('status', ['ok', 'due_soon', 'overdue'])->default('ok');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
