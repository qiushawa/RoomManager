<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Manager extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $fillable = ['username', 'name', 'email'];
    protected $hidden = ['password'];
}
