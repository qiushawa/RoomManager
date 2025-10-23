<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Borrower Model
 *
 * 此模型用於操作 borrower 資料表，對應借用者資訊。
 */
class Borrower extends Model
{
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $table = 'borrower';
    protected $primaryKey = 'student_id';
    protected $fillable = [
        'student_id',
        'email',
        'name',
        'cellphone',
        'department',
    ];
}
