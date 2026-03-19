<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacklistDetail extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'blacklist_id',
        'reason_id',
    ];

    public function blacklist()
    {
        return $this->belongsTo(Blacklist::class);
    }

    public function reason()
    {
        return $this->belongsTo(BlacklistReason::class, 'reason_id');
    }
}
