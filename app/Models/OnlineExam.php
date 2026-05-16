<?php

declare(strict_types=1);

namespace App\Models;


use App\Models\ClassSection;
use App\Models\OnlineExamStudentAnswer;
use App\Models\StudentOnlineExamStatus;
use App\Models\OnlineExamQuestionChoice;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineExam extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function model()
    {
        return $this->morphTo();
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }

    public function question_choice()
    {
        return $this->hasMany(OnlineExamQuestionChoice::class, 'online_exam_id');
    }

    public function student_attempt()
    {
        return $this->hasOne(StudentOnlineExamStatus::class, 'online_exam_id');
    }


    protected static function booted()
    {
        static::deleting(function ($exam) {

            if ($exam->isForceDeleting()) {

                OnlineExamQuestionChoice::where('online_exam_id', $exam->id)->forceDelete();
                OnlineExamStudentAnswer::where('online_exam_id', $exam->id)->forceDelete();
                StudentOnlineExamStatus::where('online_exam_id', $exam->id)->forceDelete();
            } else {

                OnlineExamQuestionChoice::where('online_exam_id', $exam->id)->delete();
                OnlineExamStudentAnswer::where('online_exam_id', $exam->id)->delete();
                StudentOnlineExamStatus::where('online_exam_id', $exam->id)->delete();
            }
        });
    }
}
