<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use App\Models\File;
use App\Models\User;
use App\Models\LeaveDetail;
use App\Models\LeaveMaster;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Leave extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'leave_master_id',
        'reason',
        'from_date',
        'to_date',
        'status',
        'session_year_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Get all of the leave_detail for the Leave
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function leave_detail()
    {
        return $this->hasMany(LeaveDetail::class);
    }

    /**
     * Get the leave_master that owns the Leave
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function leave_master()
    {
        return $this->belongsTo(LeaveMaster::class);
    }

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    protected static function booted()
    {
        static::deleting(function ($leave) {

            // Delete all leave details
            $leave->leave_detail()->delete();

            // Delete all files attached via morphMany
            $leave->file()->delete();
        });
    }
}
