<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Borrower extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'identity_code',
        'name',
        'email',
        'phone',
        'department',
        'is_active'
    ];

    public function blacklist()
    {
        return $this->hasOne(Blacklist::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }
}
