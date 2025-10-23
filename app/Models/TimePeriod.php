<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * TimePeriod Model
 *
 * 此模型用於操作 time_period 資料表，對應時段資訊。
 */
class TimePeriod extends Model
{
    public $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'time_period';
    protected $primaryKey = 'period_id';
    protected $fillable = [
        'period_id',
        'start',
        'end',
    ];

}
