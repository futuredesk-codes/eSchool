<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // attendances table
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'status')) {
                $table->tinyInteger('status')
                    ->default(0)
                    ->comment('0 - not send, 1 - send')
                    ->after('remark');
            }
        });

        // paid_installment_fees table
        Schema::table('paid_installment_fees', function (Blueprint $table) {
            if (!Schema::hasColumn('paid_installment_fees', 'status')) {
                $table->tinyInteger('status')
                    ->default(0)
                    ->comment('0 - failed, 1 - succeed')
                    ->after('payment_transaction_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('paid_installment_fees', function (Blueprint $table) {
            if (Schema::hasColumn('paid_installment_fees', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
