<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OnlineExamQuestionChoice extends Model
{
    use HasFactory, SoftDeletes;
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function online_exam()
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }
    public function questions()
    {
        return $this->belongsTo(OnlineExamQuestion::class, 'question_id')->with('options', 'answers');
    }

    protected static function booted()
    {
        static::deleting(function ($choice) {

            if ($choice->isForceDeleting()) {
                OnlineExamStudentAnswer::where('question_id', $choice->id)->forceDelete();
            } else {
                OnlineExamStudentAnswer::where('question_id', $choice->id)->delete();
            }
        });
    }
}
