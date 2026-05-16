<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeesType extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function fees_class()
    {
        return $this->hasMany(FeesClass::class, 'fees_type_id');
    }

    protected static function booted()
    {
        static::deleting(function ($feesType) {
            if ($feesType->isForceDeleting()) {
                FeesChoiceable::where('fees_type_id', $feesType->id)->forceDelete();
                FeesClass::where('fees_type_id', $feesType->id)->forceDelete();
            } else {
                FeesChoiceable::where('fees_type_id', $feesType->id)->delete();
                FeesClass::where('fees_type_id', $feesType->id)->delete();
            }
        });
    }
}
