<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactUs extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'first_name',
        'last_name', 
        'email',
        'phone',
        'message',
        'date'
    ];
    
    protected $hidden = [
        'created_at',
        'updated_at'
    ];
}
