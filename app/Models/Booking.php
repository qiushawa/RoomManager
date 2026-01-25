<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'borrower_id',
        'classroom_id',
        'start_time_slot_id',
        'end_time_slot_id',
        'reason',
        'teacher',
        'status',
        'date'
    ];   
}
