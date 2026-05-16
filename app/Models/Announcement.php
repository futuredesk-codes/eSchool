<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $hidden = ["deleted_at", "updated_at"];

    protected static function booted()
    {
        static::deleting(function ($announcement) {
            if ($announcement->isForceDeleting()) {
                $announcement->file()->withTrashed()->forceDelete();
            } else {
                $announcement->file()->delete();
            }
        });
    }

    public function table() {
        return $this->morphTo()->withTrashed();
    }

    public function file() {
        return $this->morphMany(File::class, 'modal');
    }

    public function semester() {
        return $this->belongsTo(Semester::class);
    }

}
