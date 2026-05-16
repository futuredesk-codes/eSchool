<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function exam_classes()
    {
        return $this->hasMany(ExamClass::class);
    }
    public function session_year()
    {
        return $this->belongsTo(SessionYear::class);
    }
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
    public function marks()
    {
        return $this->hasManyThrough(ExamMarks::class, ExamTimetable::class, 'exam_id', 'exam_timetable_id')->orderBy('date', 'asc');
    }
    public function timetable()
    {
        return $this->hasMany(ExamTimetable::class);
    }
    public function results()
    {
        return $this->hasMany(ExamResult::class, 'exam_id');
    }

    protected static function booted()
    {
        static::deleting(function ($exam) {

            $timetableIds = ExamTimetable::where('exam_id', $exam->id)
                ->pluck('id');

            if ($exam->isForceDeleting()) {
                ExamMarks::whereIn('exam_timetable_id', $timetableIds)->forceDelete();
                ExamTimetable::whereIn('id', $timetableIds)->forceDelete();
                ExamResult::where('exam_id', $exam->id)->forceDelete();
                ExamClass::where('exam_id', $exam->id)->forceDelete();
            } else {
                ExamMarks::whereIn('exam_timetable_id', $timetableIds)->delete();
                ExamTimetable::whereIn('id', $timetableIds)->delete();
                ExamResult::where('exam_id', $exam->id)->delete();
                ExamClass::where('exam_id', $exam->id)->delete();
            }
        });
    }
}
