<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BlacklistReason extends Model
{
    use HasFactory;
    protected $fillable = ['reason'];

    public function blacklistDetails()
    {
        return $this->hasMany(BlacklistDetail::class, 'reason_id');
    }
}
