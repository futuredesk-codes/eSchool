<?php

namespace App\Actions\Transfers;

use App\Models\Timetable;
use Illuminate\Support\Facades\DB;

class TransferClassTimetable
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        Timetable::where('session_year_id', $fromSessionId)
            ->chunkById(500, function ($rows) use ($toSessionId) {

                $data = $rows->map(function ($row) use ($toSessionId) {
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

                if (!empty($data)) {
                    DB::table('timetables')->insert($data);
                }
            });
    }
}
