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

    public const LEVEL_REJECTED = 0;
    public const LEVEL_CANCELLED = 0;
    public const LEVEL_PENDING = 10;
    public const LEVEL_APPROVED = 20;
    public const LEVEL_COURSE = 255;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_ENUMS = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_INT_TO_ENUM = [
        0 => self::STATUS_PENDING,
        1 => self::STATUS_APPROVED,
        2 => self::STATUS_REJECTED,
        3 => self::STATUS_CANCELLED,
    ];

    public const STATUS_ENUM_TO_INT = [
        self::STATUS_PENDING => 0,
        self::STATUS_APPROVED => 1,
        self::STATUS_REJECTED => 2,
        self::STATUS_CANCELLED => 3,
    ];

    protected $fillable = [
        'borrower_id',
        'classroom_id',
        'reason',
        'teacher',
        'status_enum',
        'level',
        'approved_by',
        'rejected_by',
        'approved_at',
        'rejected_at',
    ];

    protected $casts = [
        'level' => 'integer',
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
        return self::where('status_enum', self::STATUS_PENDING);
    }

    public static function activeStatusEnums(): array
    {
        return [self::STATUS_PENDING, self::STATUS_APPROVED];
    }

    public static function levelForStatus(?string $statusEnum): int
    {
        return match ($statusEnum) {
            self::STATUS_APPROVED => self::LEVEL_APPROVED,
            self::STATUS_PENDING => self::LEVEL_PENDING,
            self::STATUS_REJECTED => self::LEVEL_REJECTED,
            self::STATUS_CANCELLED => self::LEVEL_CANCELLED,
            default => self::LEVEL_PENDING,
        };
    }

    public static function enumFromLegacyStatus(int|string|null $status): string
    {
        $statusInt = is_numeric($status) ? (int) $status : 0;
        return self::STATUS_INT_TO_ENUM[$statusInt] ?? self::STATUS_PENDING;
    }

    public static function intFromStatusEnum(?string $statusEnum): int
    {
        $status = is_string($statusEnum) ? $statusEnum : self::STATUS_PENDING;
        return self::STATUS_ENUM_TO_INT[$status] ?? 0;
    }

    public static function enumFromFilterValue(mixed $rawStatus): ?string
    {
        if ($rawStatus === null) {
            return null;
        }

        $value = (string) $rawStatus;
        if ($value === '' || $value === 'all') {
            return null;
        }

        if (in_array($value, self::STATUS_ENUMS, true)) {
            return $value;
        }

        if (is_numeric($value)) {
            return self::enumFromLegacyStatus((int) $value);
        }

        return null;
    }
}
