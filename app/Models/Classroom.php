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

    // activeClassrooms 靜態方法
    public static function activeClassrooms()
    {
        return self::where('is_active', true)->get();
    }

    // 此學期被借用次數統計
    public function currentSemesterBookingCount()
    {
        
    }

}
