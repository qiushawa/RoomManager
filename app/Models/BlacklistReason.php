<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * BlacklistReason Model
 *
 * 此模型用於操作 blacklist_reason 資料表，對應黑名單原因資訊。
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
