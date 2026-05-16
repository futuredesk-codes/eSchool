<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('name');
            $table->date('end_date')->nullable()->after('start_date');
        });

        // Convert old months data to date.
        if (Schema::hasColumn('semesters', 'start_month') && Schema::hasColumn('semesters', 'end_month')) {
            DB::table('semesters')->orderBy('id')->chunk(100, function ($semesters) {
                $year = now()->year;

                foreach ($semesters as $semester) {
                    if (empty($semester->start_month) || empty($semester->end_month)) {
                        // log the issue for review
                        logger()->warning("Semester ID {$semester->id} missing month values; skipping conversion.");
                        continue;
                    }

                    $startDate = sprintf('%04d-%02d-01', $year, $semester->start_month);
                    $endDate   = sprintf('%04d-%02d-01', $year, $semester->end_month);

                    DB::table('semesters')
                        ->where('id', $semester->id)
                        ->update([
                            'start_date' => $startDate,
                            'end_date'   => $endDate,
                        ]);
                }
            });
        }

        Schema::table('semesters', function (Blueprint $table) {
            $table->dropColumn(['start_month', 'end_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('semesters', function (Blueprint $table) {
            $table->integer('start_month')->nullable()->after('name');
            $table->integer('end_month')->nullable()->after('start_month');
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
