<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blacklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'borrower_id',
        'banned_until',
    ];

    protected $casts = [
        'banned_until' => 'datetime',
    ];

    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'borrower_id');
    }

    public function blacklistDetails()
    {
        return $this->hasMany(BlacklistDetail::class);
    }

    /**
     * 目前仍在停權中的黑名單
     */
    public function scopeActive($query)
    {
        return $query->where('banned_until', '>=', now());
    }

    /**
     * 依借用人學號篩選黑名單
     */
    public function scopeForIdentityCode($query, string $identityCode)
    {
        return $query->whereHas('borrower', function ($q) use ($identityCode) {
            $q->where('identity_code', $identityCode);
        });
    }

    /**
     * 取得特定學號目前有效的停權紀錄
     */
    public static function findActiveByIdentityCode(string $identityCode): ?self
    {
        return static::query()
            ->forIdentityCode($identityCode)
            ->active()
            ->latest('banned_until')
            ->first();
    }
}