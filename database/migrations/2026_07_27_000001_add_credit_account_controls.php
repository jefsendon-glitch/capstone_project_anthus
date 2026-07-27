<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('credit_status')->default('eligible')->after('credit_balance');
            $table->timestamp('credit_suspended_at')->nullable()->after('credit_status');
            $table->string('credit_suspension_reason')->nullable()->after('credit_suspended_at');
        });

        Schema::table('sales_transactions', function (Blueprint $table) {
            // Only credit sales created after this release receive a due date. This
            // avoids incorrectly assigning due dates to legacy balances.
            $table->date('credit_due_date')->nullable()->after('payment_method');
            $table->decimal('credit_paid_amount', 10, 2)->default(0)->after('credit_due_date');
            $table->string('credit_status')->default('not_applicable')->after('credit_paid_amount');
            $table->index(['customer_id', 'payment_method', 'credit_status'], 'sales_credit_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropIndex('sales_credit_lookup');
            $table->dropColumn(['credit_due_date', 'credit_paid_amount', 'credit_status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['credit_status', 'credit_suspended_at', 'credit_suspension_reason']);
        });
    }
};
