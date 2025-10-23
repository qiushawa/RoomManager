<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Classroom Model
 *
 * 此模型用於操作 classroom 資料表，對應教室資訊。
 */
class BlacklistReason extends Model
{
    public $keyType = 'int';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = 'blacklist_reason';
    protected $primaryKey = 'reason_id';
    protected $fillable = [
        'reason_id',
        'reason',
    ];
}
