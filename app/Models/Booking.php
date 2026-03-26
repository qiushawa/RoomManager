<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'borrower_id',
        'classroom_id',
        'reason',
        'teacher',
        'status_enum',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
    ];

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function borrower()
    {
        return $this->belongsTo(Borrower::class, 'borrower_id');
    }

    public function bookingDates()
    {
        return $this->hasMany(BookingDate::class)->orderBy('date');
    }

    public function getSortedBookingDateCollection(): Collection
    {
        $this->loadMissing('bookingDates');

        $dates = $this->bookingDates
            ->pluck('date')
            ->filter()
            ->map(fn ($date) => $date instanceof Carbon ? $date->copy() : Carbon::parse($date))
            ->sortBy(fn (Carbon $date) => $date->timestamp)
            ->values();

        return $dates;
    }

    public function getDateSummaryData(string $format = 'Y-m-d', bool $includeDayCount = false): array
    {
        $dates = $this->getSortedBookingDateCollection();
        $count = $dates->count();

        if ($count === 0) {
            return [
                'first_date' => null,
                'last_date' => null,
                'count' => 0,
                'is_multi_day' => false,
                'summary' => '',
            ];
        }

        /** @var Carbon $firstDate */
        $firstDate = $dates->first();
        /** @var Carbon $lastDate */
        $lastDate = $dates->last();

        $firstText = $firstDate->format($format);
        $lastText = $lastDate->format($format);
        $isMultiDay = $count > 1 && !$firstDate->isSameDay($lastDate);

        $summary = $isMultiDay ? sprintf('%s ~ %s', $firstText, $lastText) : $firstText;
        if ($includeDayCount && $isMultiDay) {
            $summary .= sprintf('（共 %d 天）', $count);
        }

        return [
            'first_date' => $firstText,
            'last_date' => $lastText,
            'count' => $count,
            'is_multi_day' => $isMultiDay,
            'summary' => $summary,
        ];
    }

    // 未處理的預約 靜態
    public static function pending()
    {
        return self::where('status_enum', 'pending');
    }
}
