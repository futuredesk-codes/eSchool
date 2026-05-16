<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class AssignmentSubmission extends Model
{
    use HasFactory, SoftDeletes;

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(Students::class);
    }

    public function scopeAssignmentSubmissionTeachers($query)
    {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $teacher_id = $user->teacher()->select('id')->pluck('id')->first();
            $subject_teacher = SubjectTeacher::select('class_section_id', 'subject_id')->where('teacher_id', $teacher_id)->get();
            if ($subject_teacher) {
                $subject_teacher = $subject_teacher->toArray();
                $class_section_id = array_column($subject_teacher, 'class_section_id');
                $subject_id = array_column($subject_teacher, 'subject_id');
                $assignment_id = Assignment::select('id')->whereIn('class_section_id', $class_section_id)->whereIn('subject_id', $subject_id)->get()->pluck('id');
                return $query->whereIn('assignment_id', $assignment_id);
            }
            return $query;
        }
        return $query;
    }

    protected static function booted()
    {
        static::deleting(function ($submission) {

            foreach ($submission->file as $file) {
                if (Storage::disk('public')->exists($file->file_url)) {
                    Storage::disk('public')->delete($file->file_url);
                }
            }

            if ($submission->isForceDeleting()) {
                $submission->file()->forceDelete();
            } else {
                $submission->file()->delete();
            }
        });
    }
}
