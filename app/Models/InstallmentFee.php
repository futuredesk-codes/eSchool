<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstallmentFee extends Model
{
    use HasFactory, SoftDeletes;
    protected $hidden = ["created_at", "updated_at"];

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function paidInstallments(): HasMany
    {
        return $this->hasMany(PaidInstallmentFee::class, 'installment_fee_id');
    }

    //Getter Attributes
    public function getDueDateAttribute($value)
    {
        $data = getSettings('date_formate');
        return date($data['date_formate'] ?? 'd-m-Y', strtotime($value));
    }

    protected static function booted()
    {
        static::deleting(function ($installment) {

            if ($installment->isForceDeleting()) {
                $installment->paidInstallments->each->forceDelete();
            } else {
                $installment->paidInstallments->each->delete();
            }
        });
    }
}
