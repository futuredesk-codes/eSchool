<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassSubject extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function class()
    {
        return $this->belongsTo(ClassSchool::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class)->where('deleted_at', null);
    }

    public function subjectGroup()
    {
        return $this->belongsTo(ElectiveSubjectGroup::class, 'elective_subject_group_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function scopeSubjectTeacher($query, $class_section_id = null, $semester_id = null)
    {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $subjectTeacherQuery = $user->teacher->subjects();
            if ($class_section_id) {
                $subjectTeacherQuery->where('class_section_id', $class_section_id);
            }
            if ($semester_id) {
                $subjectTeacherQuery->where('semester_id', $semester_id);
            }
            $subjects_ids = $subjectTeacherQuery->pluck('subject_id');
            return $query->whereIn('subject_id', $subjects_ids);
        }
        return $query;
    }

    protected static function booted()
    {
        static::deleting(function ($classSubject) {
            // Cascade related online exams
            if ($classSubject->isForceDeleting()) {
                OnlineExamQuestion::where('class_subject_id', $classSubject->id)->forceDelete();
                OnlineExam::where('subject_id', $classSubject->id)->forceDelete();
            } else {
                OnlineExamQuestion::where('class_subject_id', $classSubject->id)->delete();
                OnlineExam::where('subject_id', $classSubject->id)->delete();
            }

            // Delete subject_teacher entries for the same subject & session year
            $classSectionIds = ClassSection::where('class_id', $classSubject->class_id)->pluck('id');
            $subjectTeacherQuery = SubjectTeacher::where('subject_id', $classSubject->subject_id)
                ->whereIn('class_section_id', $classSectionIds)
                ->where('session_year_id', $classSubject->session_year_id);

            if ($classSubject->isForceDeleting()) {
                $subjectTeacherQuery->forceDelete();
            } else {
                $subjectTeacherQuery->delete();
            }

            // Elective subject group bookkeeping
            if ($classSubject->type === 'Elective' && $classSubject->elective_subject_group_id) {
                $group = ElectiveSubjectGroup::find($classSubject->elective_subject_group_id);

                if ($group) {
                    $group->decrement('total_subjects');

                    if ($group->total_subjects <= 0) {
                        $classSubject->isForceDeleting()
                            ? $group->forceDelete()
                            : $group->delete();
                    }
                }
            }
        });
    }
}
