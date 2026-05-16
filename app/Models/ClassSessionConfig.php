<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSessionConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_id',
        'session_year_id',
        'include_semesters',
    ];

    protected $casts = [
        'include_semesters' => 'boolean',
    ];

    // Relationships

    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    public function sessionYear()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function classSections()
    {
        return $this->hasMany(ClassSection::class, 'class_id', 'class_id');
    }

    // Helper Methods

    /**
     * Check if a class has semester configuration for a given session year.
     */
    public static function classHasSemester($class_id, $session_year_id): bool
    {
        return static::where('class_id', $class_id)
            ->where('session_year_id', $session_year_id)
            ->where('include_semesters', true)
            ->exists();
    }
}
