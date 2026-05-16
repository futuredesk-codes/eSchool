<?php

namespace App\Actions;

use App\Models\Lesson;

class ResetLessonSemesters
{
    public function execute(): void
    {
        Lesson::whereNotNull('semester_id')->update(['semester_id' => null]);
    }
}
