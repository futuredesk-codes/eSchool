<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->foreignId('session_year_id')
                ->nullable()
                ->after('end_date')
                ->constrained('session_years')
                ->restrictOnDelete();
        });

        // Backfill session_year_id if default exists
        $settings = getSettings('session_year');
        $currentSessionId = $settings['session_year'] ?? null;

        if (!empty($currentSessionId)) {
            DB::table('semesters')
                ->whereNull('session_year_id')
                ->update(['session_year_id' => $currentSessionId]);
        }
    }

    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->dropForeign(['session_year_id']);
            $table->dropColumn('session_year_id');
        });
    }
};
