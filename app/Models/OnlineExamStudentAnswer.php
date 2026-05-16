<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OnlineExamStudentAnswer extends Model
{
    use HasFactory, SoftDeletes;
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function online_exam()
    {
        return $this->belongsTo(OnlineExam::class, 'online_exam_id');
    }
}
