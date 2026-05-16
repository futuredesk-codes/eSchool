<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $currentSessionYearId = DB::table('session_years')
            ->whereNull('deleted_at')
            ->where('default', 1)
            ->orderByDesc('id')
            ->value('id');

        if ($currentSessionYearId === null) {
            return;
        }

        $timestamp = now();

        DB::table('classes')
            ->select('id', 'include_semesters')
            ->orderBy('id')
            ->chunkById(1000, function ($classes) use ($currentSessionYearId, $timestamp) {
                $rows = $classes->map(function ($class) use ($currentSessionYearId, $timestamp) {
                    return [
                        'class_id'           => $class->id,
                        'session_year_id'    => $currentSessionYearId,
                        'include_semesters'  => (bool) $class->include_semesters,
                        'created_at'         => $timestamp,
                        'updated_at'         => $timestamp,
                    ];
                })->all();

                DB::table('class_session_configs')->upsert(
                    $rows,
                    ['class_id', 'session_year_id'],
                    ['include_semesters', 'updated_at']
                );
            });
    }

    public function down(): void
    {
        // The backfill is intentionally retained on rollback to avoid deleting
        // tenant-managed session config data that may have changed after deploy.
    }
};
