<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // get the settings helper
        $settings = getSettings('session_year');

        if (!isset($settings['session_year'])) {
            return; // skip migration if session_year setting is not found (its applicable to fresh installations)
        }

        // get the current session year id
        $currentSessionId = $settings['session_year'];

        // Clear existing student sessions
        DB::table('student_sessions')->truncate();

        // Fetch all students
        $students = DB::table('students')
            ->select('id', 'class_section_id')
            ->get();

        // Prepare bulk insert
        $now = now();
        $insertData = [];

        foreach ($students as $student) {
            $insertData[] = [
                'student_id' => $student->id,
                'session_year_id' => $currentSessionId,
                'previous_session_year_id' => null,
                'class_section_id' => $student->class_section_id,
                'status' => 1,
                'result' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert in chunks
        foreach (array_chunk($insertData, 500) as $chunk) {
            DB::table('student_sessions')->insert($chunk);
        }
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('student_sessions')->truncate();
    }
};
