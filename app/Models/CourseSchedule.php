<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'semester_id',
        'classroom_id',
        'course_name',
        'teacher_name',
        'day_of_week',
        'start_slot_id',
        'end_slot_id',
        'borrow_type',
        'source',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date'   => 'date:Y-m-d',
    ];

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function startSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'start_slot_id');
    }

    public function endSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'end_slot_id');
    }
}
