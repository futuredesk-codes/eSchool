<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\ClassSessionConfig;

class TransferClassSessionConfig
{
    public function execute(int $currentSessionId, int $newSessionId): void
    {
        $previousConfigs = ClassSessionConfig::where('session_year_id', $currentSessionId)
            ->get(['class_id', 'include_semesters']);

        if ($previousConfigs->isEmpty()) {
            return;
        }

        $existingClassIds = ClassSessionConfig::where('session_year_id', $newSessionId)
            ->pluck('class_id')
            ->toArray();

        $now = now();

        $toInsert = $previousConfigs
            ->reject(fn ($config) => in_array($config->class_id, $existingClassIds))
            ->map(fn ($config) => [
                'class_id' => $config->class_id,
                'session_year_id' => $newSessionId,
                'include_semesters' => $config->include_semesters,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->toArray();

        if (! empty($toInsert)) {
            ClassSessionConfig::insert($toInsert);
        }
    }
}
