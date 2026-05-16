<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mediums extends Model
{
    protected $hidden = ["deleted_at", "created_at", "updated_at"];
    use SoftDeletes;
    use HasFactory;

    protected static function booted()
    {
        static::deleting(function ($medium) {
            if ($medium->isForceDeleting()) {
                // Classes (instance-based → cascade continues)
                ClassSchool::withTrashed()
                    ->where('medium_id', $medium->id)
                    ->get()
                    ->each
                    ->forceDelete();

                // Subjects
                Subject::withTrashed()
                    ->where('medium_id', $medium->id)
                    ->get()
                    ->each
                    ->forceDelete();
            } else {
                // Classes (instance-based → cascade continues)
                ClassSchool::where('medium_id', $medium->id)
                    ->get()
                    ->each
                    ->delete();

                // Subjects
                Subject::where('medium_id', $medium->id)
                    ->get()
                    ->each
                    ->delete();
            }
        });
    }
}
