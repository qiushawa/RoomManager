<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Classroom Model
 *
 * 此模型用於操作 classroom 資料表，對應教室資訊。
 */
class Classroom extends Model
{
    protected $table = 'classroom';
    protected $primaryKey = 'room_id';
    public $timestamps = false;
    protected $fillable = [
        'room_id',
        'room_name',
        'active',
    ];
}
