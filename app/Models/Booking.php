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
        'start_slot_id',
        'end_slot_id',
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

    public function startSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'start_slot_id');
    }

    public function endSlot()
    {
        return $this->belongsTo(TimeSlot::class, 'end_slot_id');
    }
}
