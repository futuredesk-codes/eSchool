<?php

namespace App\Actions\Transfers;

use App\Models\FeesClass;
use Illuminate\Support\Facades\DB;

class TransferClassFeeType
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        FeesClass::where('session_year_id', $fromSessionId)
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
                    DB::table('fees_classes')->insert($data);
                }
            });
    }
}
