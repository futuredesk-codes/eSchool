<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassTeacher extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_section_id',
        'class_teacher_id',
        'session_year_id',
        'semester_id',
    ];

    public function classSections()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function classTeachers()
    {
        return $this->belongsTo(Teacher::class, 'class_teacher_id');
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class, 'semester_id');
    }
}
