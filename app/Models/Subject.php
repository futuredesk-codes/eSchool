<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Subject extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    // Relationships
    public function medium()
    {
        return $this->belongsTo(Mediums::class)->withTrashed();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'subject_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'subject_id');
    }

    public function examMarks(): HasMany
    {
        return $this->hasMany(ExamMarks::class, 'subject_id');
    }

    public function examTimetables(): HasMany
    {
        return $this->hasMany(ExamTimetable::class, 'subject_id');
    }

    public function studentSubjects(): HasMany
    {
        return $this->hasMany(StudentSubject::class, 'subject_id');
    }

    public function subjectTeachers(): HasMany
    {
        return $this->hasMany(SubjectTeacher::class, 'subject_id');
    }

    public function scopeSubjectTeacher($query)
    {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $subjects_ids = $user->teacher->subjects()->pluck('subject_id');
            return $query->whereIn('id', $subjects_ids);
        }
        return $query;
    }

    //Getter Attributes
    public function getImageAttribute($value)
    {
        return url(Storage::url($value));
    }

    protected static function booted()
    {
        static::deleting(function ($subject) {

            if ($subject->isForceDeleting()) {

                $subject->assignments()->forceDelete();
                $subject->classSubjects()->forceDelete();
                $subject->examMarks()->forceDelete();
                $subject->examTimetables()->forceDelete();
                $subject->studentSubjects()->forceDelete();
                $subject->subjectTeachers()->forceDelete();
            } else {

                $subject->assignments()->delete();
                $subject->classSubjects()->delete();
                $subject->examMarks()->delete();
                $subject->examTimetables()->delete();
                $subject->studentSubjects()->delete();
                $subject->subjectTeachers()->delete();
            }
        });
    }
}
