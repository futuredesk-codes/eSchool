<?php

namespace App\Actions\Transfers;

use App\Models\ClassTeacher;
use App\Models\SubjectTeacher;
use Illuminate\Support\Facades\DB;

class TransferClassTeacherAndSubjectTeacher
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        ClassTeacher::where('session_year_id', $fromSessionId)
            ->chunkById(500, function ($rows) use ($toSessionId) {

                $insertClassTeacherData = $rows->map(function (ClassTeacher $row) use ($toSessionId) {
                    return array_merge(
                        collect($row->getAttributes())
                            ->except(['id', 'created_at', 'updated_at'])
                            ->toArray(),
                        [
                            'session_year_id' => $toSessionId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                })->toArray();
                if (!empty($insertClassTeacherData)) {
                    DB::table('class_teachers')->insert($insertClassTeacherData);
                }
            });

        SubjectTeacher::where('session_year_id', $fromSessionId)
            ->chunkById(500, function ($rows) use ($toSessionId) {

                $insertSubjectTeacherData = $rows->map(function (SubjectTeacher $row) use ($toSessionId) {
                    return array_merge(
                        collect($row->getAttributes())
                            ->except(['id', 'created_at', 'updated_at'])
                            ->toArray(),
                        [
                            'session_year_id' => $toSessionId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                })->toArray();

                if (!empty($insertSubjectTeacherData)) {
                    DB::table('subject_teachers')->insert($insertSubjectTeacherData);
                }
            });
    }
}
