<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeesClass extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function fees_type()
    {
        return $this->belongsTo(FeesType::class, 'fees_type_id');
    }

    public function fees_paid()
    {
        return $this->hasMany(FeesPaid::class, 'fees_class_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id')->with('medium');
    }

    protected static function booted()
    {
        static::deleting(function ($feesClass) {

            $query = FeesChoiceable::where([
                'class_id'     => $feesClass->class_id,
                'fees_type_id' => $feesClass->fees_type_id,
            ]);

            if ($feesClass->isForceDeleting()) {
                $query->forceDelete();
            } else {
                $query->delete();
            }
        });
    }
}
