<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\ClassSubject;
use App\Models\ElectiveSubjectGroup;
use App\Models\StudentSubject;
use App\Models\Timetable;

class Semester extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'name',
        'session_year_id',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    protected $appends = ['current', 'start_month_name', 'end_month_name'];

    public function class_subjects()
    {
        return $this->hasMany(ClassSubject::class, 'semester_id', 'id')->with('subject');
    }

    public function elective_subject_groups()
    {
        return $this->hasMany(ElectiveSubjectGroup::class, 'semester_id', 'id');
    }

    public function timetables()
    {
        return $this->hasMany(Timetable::class, 'semester_id', 'id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'semester_id', 'id');
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'semester_id', 'id');
    }

    public function student_subjects()
    {
        return $this->hasMany(StudentSubject::class, 'semester_id', 'id');
    }

    protected static function booted()
    {
        static::deleting(function (Semester $semester) {
            if ($semester->isForceDeleting()) {
                $semester->class_subjects()->forceDelete();
                $semester->elective_subject_groups()->forceDelete();
                $semester->timetables()->forceDelete();
                $semester->student_subjects()->forceDelete();
            } else {
                $semester->class_subjects()->delete();
                $semester->elective_subject_groups()->delete();
                $semester->timetables()->delete();
                $semester->student_subjects()->delete();
            }
        });
    }

    public function getCurrentAttribute(): bool
    {
        $now = Carbon::today();

        // Handle semesters that wrap around the new year
        $start = $this->start_date;
        $end = $this->end_date;

        if (!$start || !$end) {
            return false;
        }

        if ($start->greaterThan($end)) {
            return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
        }

        return $now->between($start, $end);
    }

    public function getStartMonthNameAttribute(): string
    {
        return $this->start_date ? $this->start_date->translatedFormat('F') : '';
    }

    public function getEndMonthNameAttribute(): string
    {
        return $this->end_date ? $this->end_date->translatedFormat('F') : '';
    }
}
