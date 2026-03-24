<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_id',
        'classroom_id',
        'reason',
        'teacher',
        'status',
        'date'
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'borrower_id');
    }

    public function timeSlots()
    {
        return $this->belongsToMany(TimeSlot::class, 'booking_time_slot')->orderBy('start_time');
    }

    public function bookingDates()
    {
        return $this->hasMany(BookingDate::class)->orderBy('date');
    }

    // 未處理的預約 靜態
    public static function pending()
    {
        return self::where('status', 0);
    }
}
