<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Manager extends Authenticatable
{
    use Notifiable;
    protected $fillable = ['username', 'name', 'email'];
    protected $hidden = ['password'];
}
