<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tables that require session_year_id
     */
    private array $tables = [
        'class_subjects',
        'class_teachers',
        'elective_subject_groups',
        'events',
        'exam_classes',
        'fees_classes',
        'grades',
        'holidays',
        'leave_details',
        'online_exam_questions',
        'notifications',
        'subject_teachers',
        'timetables',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Add session_year_id column & FK (nullable)
         */
        foreach ($this->tables as $tableName) {
            if (
                Schema::hasTable($tableName) &&
                !Schema::hasColumn($tableName, 'session_year_id')
            ) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('session_year_id')
                        ->nullable()
                        ->after('id')
                        ->index();

                    $table->foreign('session_year_id')
                        ->references('id')
                        ->on('session_years')
                        ->onDelete('restrict');
                });
            }
        }

        /**
         * Backfill existing data ONLY if current session year exists
         *    Fresh install → no data touched   
         */
        // get the settings helper
        $settings = getSettings('session_year');

        if (isset($settings['session_year'])) {
            // get the current session year id
            $currentSessionId = $settings['session_year'];

            foreach ($this->tables as $tableName) {
                if (
                    Schema::hasTable($tableName) &&
                    Schema::hasColumn($tableName, 'session_year_id')
                ) {
                    DB::table($tableName)
                        ->whereNull('session_year_id')
                        ->update([
                            'session_year_id' => $currentSessionId,
                        ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (
                Schema::hasTable($tableName) &&
                Schema::hasColumn($tableName, 'session_year_id')
            ) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign("{$tableName}_session_year_id_foreign");
                    $table->dropColumn('session_year_id');
                });
            }
        }
    }
};
