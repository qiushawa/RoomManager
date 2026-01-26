<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Borrower extends Model
{
    use HasFactory;

    protected $fillable = [
        'identity_code',
        'name',
        'email',
        'phone',
        'department',
        'is_active'
    ];
}
