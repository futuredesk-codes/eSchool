<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ClassSection extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'id',
        'class_id',
        'section_id',
    ];

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    protected $appends = ['name', 'full_name'];

    public function class()
    {
        return $this->belongsTo(ClassSchool::class)->withTrashed();
    }

    public function section()
    {
        return $this->belongsTo(Section::class)->withTrashed();
    }

    public function classTeachers()
    {
        return $this->belongsToMany(Teacher::class, 'class_teachers', 'class_section_id', 'class_teacher_id')->withPivot('semester_id');
    }

    public function class_teachers()
    {
        return $this->hasMany(ClassTeacher::class, 'class_section_id')->select('class_teacher_id');
    }

    public function streams()
    {
        return $this->belongsTo(Stream::class)->withTrashed();
    }

    public function announcement()
    {
        return $this->morphMany(Announcement::class, 'table');
    }

    public function subject_teachers()
    {
        return $this->hasMany(SubjectTeacher::class);
    }

    public function students()
    {
        return $this->hasMany(Students::class, 'class_section_id');
    }

    public function scopeClassTeacher($query)
    {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $teacher = $user->teacher;

            return $query->where('class_teacher_id', $teacher->id);
        }

        return $query;
    }

    public function scopeSubjectTeacher($query)
    {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $class_section_ids = $user->teacher->subjects()->pluck('class_section_id');

            return $query->whereIn('id', $class_section_ids);
        }

        return $query;
    }

    public function getNameAttribute()
    {
        $name = '';
        if ($this->relationLoaded('class')) {
            $name .= $this->class->name;
        }
        if ($this->relationLoaded('class.stream')) {
            $name .= isset($this->class->stream->name) ? ' ('.$this->class->stream->name.') ' : '';
        }
        if ($this->relationLoaded('section')) {
            $name .= ' '.$this->section->name;
        }

        return $name;
    }

    public function getFullNameAttribute()
    {
        $name = '';
        if ($this->relationLoaded('class')) {
            $name .= $this->class->name;
        }

        if ($this->relationLoaded('section')) {
            $name .= ' '.$this->section->name;
        }
        if ($this->relationLoaded('class') && $this->class->relationLoaded('stream')) {
            $name .= isset($this->class->stream->name) ? ' ( '.$this->class->stream->name.' ) ' : '';
        }
        if ($this->relationLoaded('class') && $this->class->relationLoaded('medium')) {
            $name .= ' - '.$this->class->medium->name;
        }

        return $name;
    }

    protected static function booted()
    {
        static::deleting(function ($classSection) {

            /*
        |--------------------------------------------------------------------------
        | Student Sessions (BYPASS GLOBAL SCOPE)
        |--------------------------------------------------------------------------
        */
            if ($classSection->isForceDeleting()) {
                StudentSessions::withoutStudentScope()
                    ->where('class_section_id', $classSection->id)
                    ->forceDelete();
            } else {
                StudentSessions::withoutStudentScope()
                    ->where('class_section_id', $classSection->id)
                    ->delete();
            }

            /*
        |--------------------------------------------------------------------------
        | Online Exams (polymorphic, instance-based)
        |--------------------------------------------------------------------------
        */
            $onlineExams = OnlineExam::where('model_type', ClassSection::class)
                ->where('model_id', $classSection->id);

            if ($classSection->isForceDeleting()) {
                $onlineExams->withTrashed()->get()->each->forceDelete();
            } else {
                $onlineExams->get()->each->delete();
            }

            /*
        |--------------------------------------------------------------------------
        | Other owned data
        |--------------------------------------------------------------------------
        */
            if ($classSection->isForceDeleting()) {

                Assignment::where('class_section_id', $classSection->id)->forceDelete();
                Attendance::where('class_section_id', $classSection->id)->forceDelete();
                ExamResult::where('class_section_id', $classSection->id)->forceDelete();
                Lesson::where('class_section_id', $classSection->id)->forceDelete();
                SubjectTeacher::where('class_section_id', $classSection->id)->forceDelete();
                Timetable::where('class_section_id', $classSection->id)->forceDelete();

                // Students (cascade further)
                $classSection->students()->withTrashed()->forceDelete();
            } else {

                Assignment::where('class_section_id', $classSection->id)->delete();
                Attendance::where('class_section_id', $classSection->id)->delete();
                ExamResult::where('class_section_id', $classSection->id)->delete();
                Lesson::where('class_section_id', $classSection->id)->delete();
                SubjectTeacher::where('class_section_id', $classSection->id)->delete();
                Timetable::where('class_section_id', $classSection->id)->delete();

                // Students (cascade further)
                $classSection->students()->delete();
            }
        });
    }
}
