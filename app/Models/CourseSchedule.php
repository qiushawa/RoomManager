<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseSchedule extends Model
{
    protected $fillable = [
        'classroom_id',
        'course_name',
        'teacher_name',
        'day_of_week',
        'start_slot_id',
        'end_slot_id',
    ];
}
