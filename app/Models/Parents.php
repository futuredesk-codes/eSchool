<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Parents extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'id',
        'user_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'mobile',
        'image',
        'dob',
        'occupation',
        'dynamic_fields',

    ];
    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    protected $appends = ['full_name'];
    public function announcement()
    {
        return $this->morphMany(Announcement::class, 'table');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fatherRelationChild()
    {
        return $this->hasMany(Students::class, 'father_id');
    }

    public function motherRelationChild()
    {
        return $this->hasMany(Students::class, 'mother_id');
    }

    public function guardianRelationChild()
    {
        return $this->hasMany(Students::class, 'guardian_id');
    }

    public function children()
    {
        return $this->fatherRelationChild()->union($this->motherRelationChild())->union($this->guardianRelationChild());
    }

    //Getter Attributes
    public function getImageAttribute($value)
    {
        return url(Storage::url($value));
    }

    public function getFullNameAttribute() {
        return $this->first_name . ' ' . $this->last_name;
    }
}
