<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Announcement;
use App\Models\Assignment;
use App\Models\ClassSchool;
use App\Models\ClassSection;
use App\Models\ClassSessionConfig;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\ElectiveSubjectGroup;
use App\Models\Exam;
use App\Models\ExamClass;
use App\Models\Lesson;
use App\Models\OnlineExam;
use App\Models\Semester;
use App\Models\SubjectTeacher;
use App\Models\Timetable;
use Illuminate\Support\Facades\DB;

class ClassSessionConfigService
{
    /**
     * Save class semester configuration for a session year.
     * Any class whose semester config changes (toggled ON or OFF) gets
     * all its session-year data wiped so the admin starts fresh.
     */
    public function saveConfig(int $sessionYearId, array $selectedClassIds): void
    {
        DB::transaction(function () use ($sessionYearId, $selectedClassIds) {
            $allClassIds = ClassSchool::pluck('id')->toArray();

            // Determine which classes are actually changing state
            $changedClassIds = $this->getChangedClassIds($allClassIds, $sessionYearId, $selectedClassIds);

            // Upsert config first
            $this->upsertConfigs($allClassIds, $sessionYearId, $selectedClassIds);

            // Wipe all data for classes whose semester config changed
            if (! empty($changedClassIds)) {
                $this->wipeClassData($sessionYearId, $changedClassIds);
            }
        });
    }

    /**
     * Determine which class IDs are actually changing their include_semesters
     * value compared to what is currently stored in the DB.
     */
    private function getChangedClassIds(
        array $allClassIds,
        int $sessionYearId,
        array $selectedClassIds
    ): array {
        $existing = ClassSessionConfig::whereIn('class_id', $allClassIds)
            ->where('session_year_id', $sessionYearId)
            ->pluck('include_semesters', 'class_id')
            ->toArray();

        $changed = [];

        foreach ($allClassIds as $classId) {
            $newValue = in_array($classId, $selectedClassIds);
            $currentValue = isset($existing[$classId]) ? (bool) $existing[$classId] : null;

            // null means no config existed yet — treat as a change only if being turned ON
            if ($currentValue === null) {
                if ($newValue === true) {
                    $changed[] = $classId;
                }

                continue;
            }

            if ($currentValue !== $newValue) {
                $changed[] = $classId;
            }
        }

        return $changed;
    }

    /**
     * Bulk upsert all class session configs.
     */
    private function upsertConfigs(array $allClassIds, int $sessionYearId, array $selectedClassIds): void
    {
        $records = array_map(fn (int $classId) => [
            'class_id' => $classId,
            'session_year_id' => $sessionYearId,
            'include_semesters' => in_array($classId, $selectedClassIds),
            'created_at' => now(),
            'updated_at' => now(),
        ], $allClassIds);

        ClassSessionConfig::upsert(
            $records,
            ['class_id', 'session_year_id'],
            ['include_semesters', 'updated_at']
        );
    }

    /**
     * Wipe ALL session-year data for the given classes.
     *
     * Deletion order respects FK constraints:
     *   Timetable → SubjectTeacher
     *   Marks/Results → Exam/OnlineExam
     *   AssignmentSubmissions → Assignment
     *   Topics → Lessons (semester_id nulled, not deleted)
     *   ClassSubject → ElectiveSubjectGroup
     */
    public function wipeClassData(int $sessionYearId, array $classIds, bool $deleteLessons = false): void
    {
        $classSectionIds = ClassSection::whereIn('class_id', $classIds)
            ->pluck('id')
            ->toArray();

        // 1. Timetable
        // Must be wiped before SubjectTeacher (FK dependency)
        if (! empty($classSectionIds)) {
            $subjectTeacherIds = SubjectTeacher::whereIn('class_section_id', $classSectionIds)
                ->where('session_year_id', $sessionYearId)
                ->pluck('id')
                ->toArray();

            if (! empty($subjectTeacherIds)) {
                Timetable::whereIn('subject_teacher_id', $subjectTeacherIds)->forceDelete();
            }

            // Catch any timetable rows linked directly to class_section
            Timetable::whereIn('class_section_id', $classSectionIds)
                ->where('session_year_id', $sessionYearId)
                ->forceDelete();
        }

        // 2. Subject Teachers
        // Timetables already deleted above, bulk forceDelete is safe
        if (! empty($classSectionIds)) {
            SubjectTeacher::whereIn('class_section_id', $classSectionIds)
                ->where('session_year_id', $sessionYearId)
                ->forceDelete();
        }

        // 3. Class Teachers
        if (! empty($classSectionIds)) {
            ClassTeacher::whereIn('class_section_id', $classSectionIds)
                ->where('session_year_id', $sessionYearId)
                ->forceDelete();
        }

        // 4. Announcements
        Announcement::withTrashed()
            ->where('table_type', ClassSchool::class)
            ->whereIn('table_id', $classIds)
            ->forceDelete();

        if (! empty($classSectionIds)) {
            Announcement::withTrashed()
                ->where('table_type', ClassSection::class)
                ->whereIn('table_id', $classSectionIds)
                ->forceDelete();
        }

        if (! empty($subjectTeacherIds)) {
            Announcement::withTrashed()
                ->where('table_type', SubjectTeacher::class)
                ->whereIn('table_id', $subjectTeacherIds)
                ->forceDelete();
        }

        // 5. Offline Exams + Marks
        $examIds = ExamClass::whereIn('class_id', $classIds)
            ->pluck('exam_id')
            ->toArray();

        if (! empty($examIds)) {
            // forceDelete triggers booted() which cascades to
            // exam_timetables, exam_marks, and exam_results
            Exam::whereIn('id', $examIds)
                ->where('session_year_id', $sessionYearId)
                ->get()
                ->each
                ->forceDelete();
        }

        // 6. Online Exams + Results
        $onlineExamsForClasses = OnlineExam::where('model_type', ClassSchool::class)
            ->whereIn('model_id', $classIds)
            ->where('session_year_id', $sessionYearId)
            ->get();

        $onlineExamsForSections = collect();
        if (! empty($classSectionIds)) {
            $onlineExamsForSections = OnlineExam::where('model_type', ClassSection::class)
                ->whereIn('model_id', $classSectionIds)
                ->where('session_year_id', $sessionYearId)
                ->get();
        }

        $onlineExamsForClasses
            ->concat($onlineExamsForSections)
            ->each
            ->forceDelete(); // booted() cascades to question choices + student status

        // 7. Assignments + Submissions
        // Submissions must be deleted before assignments (FK dependency)
        if (! empty($classSectionIds)) {
            $assignmentIds = Assignment::whereIn('class_section_id', $classSectionIds)
                ->where('session_year_id', $sessionYearId)
                ->pluck('id')
                ->toArray();

            if (! empty($assignmentIds)) {
                // forceDelete triggers booted() on Assignment which should
                // cascade to assignment_submissions — verify your Assignment
                // model's booted() covers this, otherwise add explicit step below
                Assignment::whereIn('id', $assignmentIds)
                    ->get()
                    ->each
                    ->forceDelete();
            }
        }

        // 8. Lessons & Topics
        if (! empty($classSectionIds)) {
            $lessons = Lesson::withTrashed()
                ->whereIn('class_section_id', $classSectionIds)
                ->get();

            if ($deleteLessons) {
                // Completely remove lessons
                $lessons->each->forceDelete();
            } else {
                // Preserve lessons but remove semester linkage
                $lessonIds = $lessons->pluck('id')->toArray();
                if (! empty($lessonIds)) {
                    DB::table('lessons')
                        ->whereIn('id', $lessonIds)
                        ->update([
                            'semester_id' => null,
                            'updated_at' => now(),
                        ]);
                }
            }
        }

        // 9. Class Subjects + Elective Groups
        // ClassSubjects must be deleted before ElectiveSubjectGroup (FK)
        ClassSubject::whereIn('class_id', $classIds)
            ->where('session_year_id', $sessionYearId)
            ->forceDelete();

        ElectiveSubjectGroup::whereIn('class_id', $classIds)
            ->where('session_year_id', $sessionYearId)
            ->forceDelete();
    }
}
