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
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $table = 'classroom';
    protected $primaryKey = 'room_id';
    protected $fillable = [
        'room_id',
        'room_name',
        'active',
    ];
}
