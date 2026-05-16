<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Subject;
use App\Models\Semester;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElectiveSubjectGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    protected $fillable = [
        'session_year_id',
        'total_subjects',
        'total_selectable_subjects',
        'class_id',
        'semester_id',
    ];
    public function electiveSubjects()
    {
        // return $this->belongsToMany(Subject::class, ClassSubject::class, 'elective_subject_group_id', 'subject_id')->wherePivot('type', 'Elective')->withPivot('id as subject_id')->where('class_subjects.deleted_at',null)->withTrashed();
        return $this->hasMany(ClassSubject::class, 'elective_subject_group_id')->with('semester');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    protected static function booted()
    {
        static::deleting(function ($group) {

            // Get all class subjects under this group
            $classSubjectIds = ClassSubject::where('elective_subject_group_id', $group->id)
                ->pluck('id');

            // Cascade online exams (same rule as ClassSubject)
            if ($group->isForceDeleting()) {
                OnlineExamQuestion::whereIn('class_subject_id', $classSubjectIds)->forceDelete();
                OnlineExam::whereIn('subject_id', $classSubjectIds)->forceDelete();
            } else {
                OnlineExamQuestion::whereIn('class_subject_id', $classSubjectIds)->delete();
                OnlineExam::whereIn('subject_id', $classSubjectIds)->delete();
            }

            // Delete student_subject mappings
            $electiveSubjectIds = $group->electiveSubjects()->pluck('subject_id');
            $classSectionIds = ClassSection::where('class_id', $group->class_id)->pluck('id');

            if ($group->isForceDeleting()) {
                StudentSubject::whereIn('subject_id', $electiveSubjectIds)
                    ->whereIn('class_section_id', $classSectionIds)
                    ->forceDelete();
            } else {
                StudentSubject::whereIn('subject_id', $electiveSubjectIds)
                    ->whereIn('class_section_id', $classSectionIds)
                    ->delete();
            }

            // Delete elective subjects
            $group->isForceDeleting()
                ? $group->electiveSubjects()->forceDelete()
                : $group->electiveSubjects()->delete();

            // Delete class subjects under this group
            $group->isForceDeleting()
                ? ClassSubject::whereIn('id', $classSubjectIds)->forceDelete()
                : ClassSubject::whereIn('id', $classSubjectIds)->delete();
        });
    }
}
