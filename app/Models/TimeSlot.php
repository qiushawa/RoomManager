<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time'
    ];

    public function courseSchedules()
    {
        return $this->belongsToMany(CourseSchedule::class, 'course_schedule_time_slots');
    }
}
