<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year',
        'semester',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function courseSchedules()
    {
        return $this->hasMany(CourseSchedule::class);
    }

    /**
     * 取得包含指定日期的學期
     */
    public static function findByDate($date)
    {
        return static::where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }

    /**
     * 取得與日期範圍重疊的學期
     */
    public static function overlapping($startDate, $endDate)
    {
        return static::where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->get();
    }

    /**
     * 顯示名稱 (例: "114學年 上學期")
     */
    public function getDisplayNameAttribute(): string
    {
        $label = $this->semester === 1 ? '上學期' : '下學期';
        return "{$this->academic_year}學年 {$label}";
    }
}
