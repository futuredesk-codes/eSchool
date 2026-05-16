<?php

namespace App\Services;

use App\Actions\Transfers\TransferClassFeeType;
use App\Actions\Transfers\TransferClassSessionConfig;
use App\Actions\Transfers\TransferClassSubject;
use App\Actions\Transfers\TransferClassTeacherAndSubjectTeacher;
use App\Actions\Transfers\TransferClassTimetable;
use App\Actions\Transfers\TransferExamGrades;
use App\Actions\Transfers\TransferLeaveSettings;
use App\Models\ClassSubject;
use App\Models\ClassTeacher;
use App\Models\Exam;
use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\InstallmentFee;
use App\Models\Leave;
use App\Models\LeaveDetail;
use App\Models\OnlineExam;
use App\Models\Semester;
use App\Models\SessionYear;
use App\Models\SubjectTeacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SessionYearService
{
    public function store(array $data): SessionYear
    {
        return DB::transaction(function () use ($data) {

            $startDate = Carbon::createFromFormat('d-m-Y', $data['start_date'])->toDateString();
            $endDate = Carbon::createFromFormat('d-m-Y', $data['end_date'])->toDateString();
            $feesDueDate = Carbon::createFromFormat('d-m-Y', $data['fees_due_date'])->toDateString();

            if ($feesDueDate < $startDate || $feesDueDate > $endDate) {
                throw new \DomainException(
                    trans('fees_due_date_must_be_between_session_dates')
                );
            }

            $sessionYear = SessionYear::create([
                'name' => $data['name'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'fee_due_date' => $feesDueDate,
                'fee_due_charges' => $data['fees_due_charges'],
                'free_app_use_date' => isset($data['free_app_use_date'])
                    ? Carbon::createFromFormat('d-m-Y', $data['free_app_use_date'])->toDateString()
                    : null,
                'include_fee_installments' => (int) ($data['fees_installment'] ?? 0),
            ]);

            if (! empty($data['fees_installment']) && $data['fees_installment'] == '1') {
                $this->storeInstallments(
                    $sessionYear->id,
                    $data['installment_data'],
                    $startDate,
                    $endDate
                );
            }

            if (! empty($data['semester_data'])) {
                $this->storeSemesters(
                    $sessionYear->id,
                    $data['semester_data'],
                    $startDate,
                    $endDate
                );
            }

            $this->handleTransfers($sessionYear->id, $data);

            return $sessionYear;
        });
    }

    protected function storeInstallments(
        int $sessionYearId,
        array $installments,
        string $startDate,
        string $endDate
    ): void {
        $rows = [];

        foreach ($installments as $installment) {
            $dueDate = Carbon::createFromFormat('d-m-Y', $installment['due_date'])->toDateString();

            if ($dueDate < $startDate || $dueDate > $endDate) {
                throw new \DomainException(
                    trans('installment_due_date_must_be_within_session_dates')
                );
            }

            $rows[] = [
                'name' => $installment['name'],
                'due_date' => $dueDate,
                'due_charges' => $installment['due_charges'],
                'session_year_id' => $sessionYearId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($rows)) {
            InstallmentFee::insert($rows);
        }
    }

    protected function storeSemesters(
        int $sessionYearId,
        array $semesters,
        string $startDate,
        string $endDate
    ): void {
        $rows = [];

        foreach ($semesters as $semester) {
            $semStart = Carbon::createFromFormat('d-m-Y', $semester['start_date'])->toDateString();
            $semEnd = Carbon::createFromFormat('d-m-Y', $semester['end_date'])->toDateString();

            if ($semStart < $startDate || $semEnd > $endDate) {
                throw new \DomainException(
                    trans('semester_dates_must_be_within_session_dates')
                );
            }

            $rows[] = [
                'name' => $semester['name'],
                'start_date' => $semStart,
                'end_date' => $semEnd,
                'session_year_id' => $sessionYearId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (! empty($rows)) {
            Semester::insert($rows);
        }
    }

    protected function handleTransfers(int $newSessionId, array $data): void
    {
        // Use the explicitly chosen source session year; fall back to the system default
        $currentSessionId = ! empty($data['source_session_year_id'])
            ? (int) $data['source_session_year_id']
            : getSettings('session_year')['session_year'];

        if (! empty($data['semester_data'])) {
            app(TransferClassSessionConfig::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_class_subject'] ?? '0') == '1') {
            app(TransferClassSubject::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_class_teacher_subject'] ?? '0') == '1') {
            app(TransferClassTeacherAndSubjectTeacher::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_class_timetable'] ?? '0') == '1') {
            app(TransferClassTimetable::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_exam_grades'] ?? '0') == '1') {
            app(TransferExamGrades::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_class_fee_type'] ?? '0') == '1') {
            app(TransferClassFeeType::class)
                ->execute($currentSessionId, $newSessionId);
        }

        if (($data['transfer_leave_settings'] ?? '0') == '1') {
            app(TransferLeaveSettings::class)
                ->execute($currentSessionId, $newSessionId);
        }

        // Note: semester transfer is handled via storeSemesters() using semester_data
    }

    // Clear Data of Session Year
    public function clearSpecificData(int $sessionYearId, array $types): void
    {
        SessionYear::findOrFail($sessionYearId);

        DB::transaction(function () use ($sessionYearId, $types) {

            foreach ($types as $type) {
                match ($type) {
                    'class_subject' => $this->deleteClassSubjects($sessionYearId),
                    'class_teachers' => $this->deleteClassTeachers($sessionYearId),
                    'fees_transactions' => $this->deleteFeesTransactions($sessionYearId),
                    'notifications' => $this->deleteNotifications($sessionYearId),
                    'timetable' => $this->deleteTimetable($sessionYearId),
                    'attendance' => $this->deleteAttendance($sessionYearId),
                    'assignments' => $this->deleteAssignments($sessionYearId),
                    'exams' => $this->deleteExams($sessionYearId),
                    'grades' => $this->deleteGrades($sessionYearId),
                    'announcements' => $this->deleteAnnouncements($sessionYearId),
                    'holidays' => $this->deleteHolidays($sessionYearId),
                    'events' => $this->deleteEvents($sessionYearId),
                    'leaves' => $this->deleteLeaves($sessionYearId),
                    'allowed_leave_days' => $this->deleteAllowedLeaveDays($sessionYearId),
                };
            }
        });
    }

    private function deleteClassSubjects(int $sessionYearId): void
    {
        ClassSubject::where('session_year_id', $sessionYearId)->delete();
    }

    private function deleteClassTeachers(int $sessionYearId): void
    {
        ClassTeacher::where('session_year_id', $sessionYearId)->delete();
        SubjectTeacher::where('session_year_id', $sessionYearId)->delete();
    }

    private function deleteFeesTransactions(int $sessionYearId): void
    {
        FeesChoiceable::where('session_year_id', $sessionYearId)->delete();
        FeesClass::where('session_year_id', $sessionYearId)->delete();
        InstallmentFee::where('session_year_id', $sessionYearId)->delete();
        FeesPaid::where('session_year_id', $sessionYearId)->delete();
    }

    private function deleteNotifications(int $sessionYearId): void
    {
        DB::table('notifications')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteAttendance(int $sessionYearId): void
    {
        DB::table('attendances')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteAssignments(int $sessionYearId): void
    {
        DB::table('assignments')
            ->where('session_year_id', $sessionYearId)
            ->delete();

        DB::table('assignment_submissions')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteExams(int $sessionYearId): void
    {
        Exam::where('session_year_id', $sessionYearId)
            ->chunkById(200, function ($onlineExams) {
                foreach ($onlineExams as $onlineExam) {
                    $onlineExam->delete();
                }
            });

        OnlineExam::where('session_year_id', $sessionYearId)
            ->chunkById(200, function ($onlineExams) {
                foreach ($onlineExams as $onlineExam) {
                    $onlineExam->delete();
                }
            });
    }

    private function deleteTimetable(int $sessionYearId): void
    {
        DB::table('timetables')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteGrades(int $sessionYearId): void
    {
        DB::table('grades')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteAnnouncements(int $sessionYearId): void
    {
        DB::table('announcements')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteEvents(int $sessionYearId): void
    {
        DB::table('events')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteHolidays(int $sessionYearId): void
    {
        DB::table('holidays')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }

    private function deleteLeaves(int $sessionYearId): void
    {
        Leave::where('session_year_id', $sessionYearId)->delete();

        LeaveDetail::where('session_year_id', $sessionYearId)->delete();
    }

    private function deleteAllowedLeaveDays(int $sessionYearId): void
    {
        DB::table('leave_masters')
            ->where('session_year_id', $sessionYearId)
            ->delete();
    }
}
