<?php

namespace App\Services\Booking;

use App\Models\Blacklist;
use App\Models\Booking;
use App\Models\Borrower;
use App\Services\BookingSlotLockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingCreationService
{
    public function __construct(
        private readonly BookingConflictService $bookingConflictService,
        private readonly BookingSlotLockService $bookingSlotLockService,
    ) {
    }

    /**
     * @param array<string,mixed> $requestData
     * @return array{booking:Booking,selection_rows:Collection<int,array{date:string,time_slot_ids:array<int,int>}>,borrower:Borrower}
     */
    public function create(array $requestData): array
    {
        /** @var array<string,mixed> $applicantData */
        $applicantData = (array) ($requestData['applicant'] ?? []);

        $activeBlacklist = Blacklist::findActiveByIdentityCode((string) ($applicantData['identity_code'] ?? ''));
        if ($activeBlacklist) {
            throw ValidationException::withMessages([
                'applicant.identity_code' => '此學號目前停權中，停權至 '.$activeBlacklist->banned_until->format('Y-m-d').'。',
            ]);
        }

        $borrower = Borrower::firstOrCreate(
            [
                'identity_code' => (string) $applicantData['identity_code'],
                'email' => (string) $applicantData['email'],
            ],
            [
                'name' => (string) $applicantData['name'],
                'phone' => $applicantData['phone'] ?? null,
                'department' => $applicantData['department'] ?? null,
            ]
        );

        $selectionRows = $this->normalizeSelectionRows($requestData);

        if ($selectionRows->isEmpty()) {
            throw ValidationException::withMessages([
                'selections' => '請至少選擇一筆借用日期與時段。',
            ]);
        }

        $selectionConflict = $this->bookingConflictService->findSelectionConflict(
            (int) $requestData['classroom_id'],
            $selectionRows->all()
        );

        if ($selectionConflict) {
            throw ValidationException::withMessages([
                'selections' => sprintf(
                    '時段衝突：%s 第 %s 節已被預約。',
                    $selectionConflict['date'],
                    implode('、', $selectionConflict['slot_names'])
                ),
            ]);
        }

        $booking = DB::transaction(function () use ($requestData, $borrower, $applicantData, $selectionRows): Booking {
            $conflictInTx = $this->bookingConflictService->findSelectionConflict(
                (int) $requestData['classroom_id'],
                $selectionRows->all(),
                true
            );

            if ($conflictInTx) {
                throw ValidationException::withMessages([
                    'selections' => '所選時段已有其他申請，請重新整理後再試。',
                ]);
            }

            $booking = new Booking();
            $booking->classroom_id = (int) $requestData['classroom_id'];
            $booking->borrower_id = (int) $borrower->id;
            $booking->reason = $applicantData['reason'] ?? null;
            $booking->teacher = $applicantData['teacher'] ?? null;
            $booking->status_enum = Booking::STATUS_PENDING;
            $booking->level = Booking::levelForStatus(Booking::STATUS_PENDING);
            $booking->save();

            foreach ($selectionRows as $selection) {
                $bookingDate = $booking->bookingDates()->create([
                    'date' => $selection['date'],
                ]);
                $bookingDate->timeSlots()->sync($selection['time_slot_ids']);
            }

            $this->bookingSlotLockService->syncForBooking($booking);
            $booking->load(['classroom', 'borrower', 'bookingDates.timeSlots']);

            return $booking;
        });

        return [
            'booking' => $booking,
            'selection_rows' => $selectionRows,
            'borrower' => $borrower,
        ];
    }

    /**
     * @param array<string,mixed> $requestData
     * @return Collection<int,array{date:string,time_slot_ids:array<int,int>}>
     */
    private function normalizeSelectionRows(array $requestData): Collection
    {
        return collect($requestData['selections'] ?? [
            [
                'date' => $requestData['date'] ?? null,
                'time_slot_ids' => $requestData['time_slot_ids'] ?? [],
            ],
        ])
            ->filter(fn ($item) => is_array($item) && !empty($item['date']) && !empty($item['time_slot_ids']))
            ->map(function ($item): array {
                $slotIds = collect($item['time_slot_ids'])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                return [
                    'date' => (string) $item['date'],
                    'time_slot_ids' => $slotIds,
                ];
            })
            ->filter(fn ($item) => !empty($item['time_slot_ids']))
            ->groupBy('date')
            ->map(function ($items, $date): array {
                $mergedSlotIds = collect($items)
                    ->flatMap(fn ($item) => $item['time_slot_ids'])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                return [
                    'date' => (string) $date,
                    'time_slot_ids' => $mergedSlotIds,
                ];
            })
            ->values();
    }
}
