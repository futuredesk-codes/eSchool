<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Shift;
use App\Models\EducationalProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClassSchool extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $table = 'classes';
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    protected $appends = ['full_name'];

    protected $casts = [
        'include_semesters' => 'boolean',
    ];

    public function announcement()
    {
        return $this->morphMany(Announcement::class, 'table');
    }

    public function medium()
    {
        return $this->belongsTo(Mediums::class)->select('name', 'id')->withTrashed();
    }

    public function streams()
    {
        return $this->belongsTo(Stream::class, 'stream_id')->select('id', 'name');
    }

    public function shifts()
    {
        return $this->belongsTo(Shift::class, 'shift_id')->select('title', 'id', 'start_time', 'end_time');
    }

    public function educational_program()
    {
        return $this->belongsTo(EducationalProgram::class, 'educational_program_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'class_sections', 'class_id', 'section_id')->wherePivot('deleted_at', null);
    }

    public function coreSubject()
    {
        return $this->hasMany(ClassSubject::class, 'class_id')->where('type', 'Compulsory')->with('subject', 'semester');
    }

    public function electiveSubject()
    {
        return $this->hasMany(ClassSubject::class, 'class_id')->where('type', 'Elective')->with('subject', 'subjectGroup');
    }

    public function allSubjects()
    {
        return $this->hasMany(ClassSubject::class, 'class_id');
    }

    public function electiveSubjectGroup()
    {
        return $this->hasMany(ElectiveSubjectGroup::class, 'class_id');
    }

    public function fees_class()
    {
        return $this->hasMany(FeesClass::class, 'class_id')->with('fees_type');
    }

    public function getFullNameAttribute()
    {
        $name = $this->name;
        if ($this->relationLoaded('stream')) {
            $name .= isset($this->stream->name) ? ' (' . $this->stream->name . ') ' : '';
        }
        if ($this->relationLoaded('medium') && $this->medium) {
            $name .= ' - ' . $this->medium->name;
        }
        return $name;
    }

    public function classSections()
    {
        return $this->hasMany(ClassSection::class, 'class_id');
    }

    public function sessionConfigs()
    {
        return $this->hasMany(ClassSessionConfig::class, 'class_id');
    }

    public function scopeWithSessionConfigFor(Builder $query, $sessionYearId): Builder
    {
        if (empty($sessionYearId)) {
            return $query;
        }

        return $query->with([
            'sessionConfigs' => fn($configQuery) => $configQuery->where('session_year_id', $sessionYearId),
        ]);
    }

    public function scopeWhereIncludeSemestersForSession(Builder $query, $sessionYearId, bool $enabled = true): Builder
    {
        $expectedValue = $enabled ? 1 : 0;

        return $query->join('class_session_configs as csc', function ($join) use ($sessionYearId) {
            $join->on('classes.id', '=', 'csc.class_id')
                ->where('csc.session_year_id', '=', $sessionYearId);
        })->where('csc.include_semesters', $expectedValue)
            ->select('classes.*');
    }

    public function getIncludeSemestersForSession($sessionYearId)
    {
        if (empty($sessionYearId)) {
            $sessionYearId = session('session_year') ?: (getSettings('session_year')['session_year'] ?? null);
        }

        if (empty($sessionYearId)) {
            return null;
        }

        $config = $this->relationLoaded('sessionConfigs')
            ? $this->sessionConfigs->firstWhere('session_year_id', $sessionYearId)
            : $this->sessionConfigs()->where('session_year_id', $sessionYearId)->first();

        if ($config === null) {
            return null;
        }

        return (bool) $config->include_semesters;
    }

    public function syncIncludeSemestersForSession($sessionYearId, bool $includeSemesters): void
    {
        if (empty($sessionYearId)) {
            return;
        }

        $this->sessionConfigs()->updateOrCreate(
            ['session_year_id' => $sessionYearId],
            ['include_semesters' => $includeSemesters]
        );
    }

    public function getIncludeSemestersAttribute($value): bool
    {
        $sessionYearId = app()->bound('current_session_id')
            ? app('current_session_id')
            : (session('session_year') ?: (getSettings('session_year')['session_year'] ?? null));

        if (empty($sessionYearId)) {
            return false;
        }

        return $this->getIncludeSemestersForSession($sessionYearId);
    }


    protected static function booted()
    {
        static::deleting(function ($class) {

            // Online Exams (POLYMORPHIC — MUST be instance-based)
            $onlineExams = OnlineExam::where('model_type', ClassSchool::class)
                ->where('model_id', $class->id);

            if ($class->isForceDeleting()) {
                $onlineExams->withTrashed()->get()->each->forceDelete();
            } else {
                $onlineExams->get()->each->delete();
            }

            // Delete session configs (no soft deletes)
            $class->sessionConfigs()->delete();

            if ($class->isForceDeleting()) {

                // Force delete class-level relations
                ClassSubject::where('class_id', $class->id)->forceDelete();
                ExamClass::where('class_id', $class->id)->forceDelete();
                FeesClass::where('class_id', $class->id)->forceDelete();

                // Force delete class sections (will cascade further)
                $class->classSections()->withTrashed()->get()->each->forceDelete();
            } else {

                // Soft delete class-level relations
                ClassSubject::where('class_id', $class->id)->delete();
                ExamClass::where('class_id', $class->id)->delete();
                FeesClass::where('class_id', $class->id)->delete();

                // Soft delete class sections (will cascade further)
                $class->classSections()->get()->each->delete();
            }
        });
    }
}
