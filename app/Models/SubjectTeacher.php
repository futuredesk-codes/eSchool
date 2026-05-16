<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class SubjectTeacher extends Model
{
    use SoftDeletes;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->with('class.medium', 'section')->withTrashed();
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class)->withTrashed();
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class)->with('user')->withTrashed();
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class, 'subject_teacher_id');
    }

    public function scopeSubjectTeacher($query, $class_section_id = null)
    {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $teacher_id = $user->teacher()->pluck('id');
            return $query->whereIn('teacher_id', $teacher_id);
        }
        return $query;
    }

    protected static function booted()
    {
        static::deleting(function ($subjectTeacher) {

            if ($subjectTeacher->isForceDeleting()) {
                $subjectTeacher->timetables()->forceDelete();
            } else {
                $subjectTeacher->timetables()->delete();
            }
        });
    }
}
