<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SessionYear extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'fee_due_date',
        'fee_due_charges',
        'free_app_use_date',
        'fees_installment',
    ];

    use SoftDeletes;

    /* ======================
     | Core Academic Data
     ====================== */

    public function exams()
    {
        return $this->hasMany(Exam::class, 'session_year_id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'session_year_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'session_year_id');
    }

    public function studentSessions()
    {
        return $this->hasMany(StudentSessions::class, 'session_year_id');
    }

    public function classTeacher()
    {
        return $this->hasMany(ClassTeacher::class, 'session_year_id');
    }

    public function subjectTeacher()
    {
        return $this->hasMany(SubjectTeacher::class, 'session_year_id');
    }

    public function classSubject()
    {
        return $this->hasMany(StudentSubject::class, 'session_year_id');
    }

    public function electiveSubjectGroup()
    {
        return $this->hasMany(ElectiveSubjectGroup::class, 'session_year_id');
    }

    public function grade()
    {
        return $this->hasMany(Grade::class, 'session_year_id');
    }

    public function leave()
    {
        return $this->hasMany(Leave::class, 'session_year_id');
    }

    public function leaveMaster()
    {
        return $this->hasMany(LeaveMaster::class, 'session_year_id');
    }

    public function onlineExamQuestion()
    {
        return $this->hasMany(OnlineExamQuestion::class, 'session_year_id');
    }

    public function onlineExam()
    {
        return $this->hasMany(OnlineExam::class, 'session_year_id');
    }

    /* ======================
     | Fees & Finances
     ====================== */

    public function feesPaids()
    {
        return $this->hasMany(FeesPaid::class, 'session_year_id');
    }

    public function feesChoiceables()
    {
        return $this->hasMany(FeesChoiceable::class, 'session_year_id');
    }

    public function fee_installments()
    {
        return $this->hasMany(InstallmentFee::class, 'session_year_id');
    }
    /* ======================
     | Meta Data
     ====================== */

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'session_year_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'session_year_id');
    }

    public function holidays()
    {
        return $this->hasMany(Holiday::class, 'session_year_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'session_year_id');
    }

    // Cascade delete all related data when a session year is deleted
    protected static function booted()
    {
        static::deleting(function (SessionYear $sessionYear) {

            if ((int) $sessionYear->default === 1) {
                throw new \Exception('Default session year cannot be deleted');
            }

            $relations = [
                // Core Academic
                'exams',
                'assignments',
                'attendances',
                'studentSessions',
                'classTeacher',
                'subjectTeacher',
                'classSubject',
                'electiveSubjectGroup',
                'grade',
                'leave',
                'leaveMaster',
                'onlineExamQuestion',
                'onlineExam',

                // Fees
                'feesPaids',
                'feesChoiceables',
                'fee_installments',

                // Meta
                'announcements',
                'events',
                'holidays',
                'notifications',
            ];

            foreach ($relations as $relation) {
                $sessionYear
                    ->getRelationValue($relation)
                    ->each(function ($model) use ($sessionYear) {
                        $sessionYear->isForceDeleting()
                            ? $model->forceDelete()
                            : $model->delete();
                    });
            }
        });
    }
}
