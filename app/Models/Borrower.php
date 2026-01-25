<?php

namespace App\Models;

class Borrower extends Model
{
    protected $fillable = [
        'identity_code',
        'name',
        'email',
        'phone',
        'department',
        'is_active'
    ];
}
