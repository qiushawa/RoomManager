<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Manager Model
 *
 * 此模型用於操作 manager 資料表，對應管理者資訊。
 */
class Manager extends Authenticatable
{
    use Notifiable;

    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $table = 'manager';
    protected $primaryKey = 'account';
    protected $fillable = [
        'account',
        'password',
        'name',
        'email',
    ];

    protected $hidden = [
        'password',
    ];
}
