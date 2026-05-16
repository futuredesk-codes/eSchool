<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use SoftDeletes;
    use HasFactory;
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function students(): HasMany
    {
        return $this->hasMany(Students::class);
    }

    protected static function booted()
    {
        static::deleting(function ($category) {
            if ($category->isForceDeleting()) {
                $category->students()->forceDelete();
            } else {
                $category->students()->delete();
            }
        });
    }
}
