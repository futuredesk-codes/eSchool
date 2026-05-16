<?php

use App\Models\Semester;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Guard 1: Skip if no session year is configured (fresh installation)
        $settings = getSettings('session_year');

        if (!isset($settings['session_year'])) {
            return;
        }

        $currentSessionYearId = $settings['session_year'];

        // Guard 2: Skip if no semesters exist at all
        if (!Semester::exists()) {
            return;
        }

        // Guard 3: Skip if no active semester exists for current session year
        $currentSemester = Semester::where('session_year_id', $currentSessionYearId)->get()->first(function ($semester) {
            return $semester->current;
        });

        if (!$currentSemester) {
            return;
        }


        // 1. EXAMS — derive start date from earliest exam_timetable entry
        DB::table('exams as e')
            ->join(
                DB::raw('(SELECT exam_id, MIN(date) as earliest_date FROM exam_timetables GROUP BY exam_id) as et'),
                'et.exam_id', '=', 'e.id'
            )
            ->join('semesters as s', function ($join) {
                $join->on('s.session_year_id', '=', 'e.session_year_id')
                     ->whereRaw('et.earliest_date BETWEEN s.start_date AND s.end_date');
            })
            ->whereNull('e.semester_id')
            ->whereNull('e.deleted_at')
            ->update(['e.semester_id' => DB::raw('s.id')]);


        // 2. ONLINE EXAMS — match DATE(start_date) to a real semester range
        DB::table('online_exams as oe')
            ->join('semesters as s', function ($join) {
                $join->on('s.session_year_id', '=', 'oe.session_year_id')
                     ->whereRaw('DATE(oe.start_date) BETWEEN s.start_date AND s.end_date');
            })
            ->whereNull('oe.semester_id')
            ->whereNull('oe.deleted_at')
            ->update(['oe.semester_id' => DB::raw('s.id')]);


        // 3. ASSIGNMENTS — match due_date to a real semester range
        DB::table('assignments as a')
            ->join('semesters as s', function ($join) {
                $join->on('s.session_year_id', '=', 'a.session_year_id')
                     ->whereRaw('a.due_date BETWEEN s.start_date AND s.end_date');
            })
            ->whereNull('a.semester_id')
            ->whereNull('a.deleted_at')
            ->update(['a.semester_id' => DB::raw('s.id')]);


        // 4. SUBJECT TEACHERS → pin to current semester of current session year
        DB::table('subject_teachers')
            ->where('session_year_id', $currentSessionYearId)
            ->whereNull('semester_id')
            ->update(['semester_id' => $currentSemester->id]);

        // 5. CLASS TEACHERS → assign to ALL semesters of the current session year
        $allSemesters = Semester::where('session_year_id', $currentSessionYearId)->get();

        foreach ($allSemesters as $semester) {
            if ($semester->id === $currentSemester->id) {
                continue; // skip current — handled by UPDATE below
            }

            DB::statement("
                INSERT INTO class_teachers (class_section_id, class_teacher_id, session_year_id, semester_id, created_at, updated_at)
                SELECT class_section_id, class_teacher_id, session_year_id, ?, created_at, updated_at
                FROM class_teachers
                WHERE session_year_id = ?
                  AND semester_id IS NULL
                  AND deleted_at IS NULL
            ", [$semester->id, $currentSessionYearId]);
        }

        // UPDATE original NULL rows to current semester
        DB::table('class_teachers')
            ->where('session_year_id', $currentSessionYearId)
            ->whereNull('semester_id')
            ->whereNull('deleted_at')
            ->update(['semester_id' => $currentSemester->id]);

        // 6. ANNOUNCEMENTS — SubjectTeacher-type only
        DB::table('announcements')
            ->where('session_year_id', $currentSessionYearId)
            ->whereNull('semester_id')
            ->where('table_type', 'App\Models\SubjectTeacher')
            ->update(['semester_id' => $currentSemester->id]);
    }

    public function down(): void
    {
        // Not safely reversible
    }
};