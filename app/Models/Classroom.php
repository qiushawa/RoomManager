<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    protected $fillable = ['code', 'name', 'is_active'];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function courseSchedules()
    {
        return $this->hasMany(CourseSchedule::class);
    }
}
