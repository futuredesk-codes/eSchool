<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ['deleted_at', 'updated_at'];

    protected static function booted()
    {
        static::deleting(function ($assignment) {

            // Delete assignment files (PHYSICAL FILES)
            foreach ($assignment->file as $file) {
                if (Storage::disk('public')->exists($file->file_url)) {
                    Storage::disk('public')->delete($file->file_url);
                }
            }

            if ($assignment->isForceDeleting()) {
                $assignment->file()->forceDelete();
                $assignment->submissions()->forceDelete();
            } else {
                $assignment->file()->delete();
                $assignment->submissions()->delete();
            }
        });
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class)->withTrashed();
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class)->withTrashed();
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    // Backward-compatible alias for existing singular calls.
    public function submission()
    {
        return $this->submissions();
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->with('class', 'section');
    }

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    public function scopeAssignmentTeachers($query)
    {
        $user = Auth::user();

        if (! $user->hasRole('Teacher')) {
            return $query;
        }

        $teacherId = $user->teacher?->id;

        if (! $teacherId) {
            return $query;
        }

        return $query->where(function ($q) use ($teacherId) {

            $q->whereExists(function ($subQuery) use ($teacherId) {

                $subQuery->selectRaw('1')
                    ->from('subject_teachers')
                    ->join('class_sections', 'class_sections.id', '=', 'subject_teachers.class_section_id')
                    ->whereColumn('subject_teachers.class_section_id', 'assignments.class_section_id')
                    ->whereColumn('subject_teachers.subject_id', 'assignments.subject_id')
                    ->where('subject_teachers.teacher_id', $teacherId)

                    ->where(function ($semesterQuery) {

                        // Semester based classes
                        $semesterQuery->where(function ($q1) {

                            $q1->whereExists(function ($configQuery) {

                                $configQuery->selectRaw('1')
                                    ->from('class_session_configs')
                                    ->whereColumn('class_session_configs.class_id', 'class_sections.class_id')
                                    ->whereColumn('class_session_configs.session_year_id', 'assignments.session_year_id')
                                    ->where('class_session_configs.include_semesters', true);
                            })
                                ->whereColumn(
                                    'subject_teachers.semester_id',
                                    'assignments.semester_id'
                                );
                        })

                        // Non-semester based classes
                            ->orWhere(function ($q2) {

                                $q2->whereNotExists(function ($configQuery) {

                                    $configQuery->selectRaw('1')
                                        ->from('class_session_configs')
                                        ->whereColumn('class_session_configs.class_id', 'class_sections.class_id')
                                        ->whereColumn('class_session_configs.session_year_id', 'assignments.session_year_id')
                                        ->where('class_session_configs.include_semesters', true);
                                });
                            });
                    });
            });
        });
    }
}
