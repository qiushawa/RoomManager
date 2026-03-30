<?php

namespace App\Services\Booking;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingConflictService
{
    /**
     * @param array<int, array{date:string,time_slot_ids:array<int,int>}> $selectionRows
     * @return array{date:string,slot_names:array<int,string>}|null
     */
    public function findSelectionConflict(int $classroomId, array $selectionRows, bool $forUpdate = false): ?array
    {
        $pairs = collect($selectionRows)
            ->flatMap(function (array $selection): array {
                $date = (string) ($selection['date'] ?? '');
                $slotIds = collect($selection['time_slot_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->filter(fn ($id) => $id > 0)
                    ->unique()
                    ->values();

                if ($date === '' || $slotIds->isEmpty()) {
                    return [];
                }

                return $slotIds->map(fn ($slotId) => [
                    'date' => $date,
                    'time_slot_id' => (int) $slotId,
                ])->all();
            })
            ->unique(fn (array $pair) => $pair['date'].'#'.$pair['time_slot_id'])
            ->values();

        if ($pairs->isEmpty()) {
            return null;
        }

        $pairKeys = $pairs
            ->map(fn (array $pair) => $pair['date'].'#'.$pair['time_slot_id'])
            ->values()
            ->all();

        $query = DB::table('booking_slot_locks as bsl')
            ->join('time_slots as ts', 'ts.id', '=', 'bsl.time_slot_id')
            ->select(['bsl.date', 'bsl.time_slot_id', 'ts.name as slot_name', 'ts.start_time'])
            ->where('bsl.classroom_id', $classroomId)
            ->whereIn(
                DB::raw("CONCAT(DATE_FORMAT(bsl.date, '%Y-%m-%d'), '#', bsl.time_slot_id)"),
                $pairKeys
            );

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        /** @var Collection<int, object{date:string,slot_name:string}> $conflicts */
        $conflicts = $query
            ->orderBy('bsl.date')
            ->orderBy('ts.start_time')
            ->get();

        if ($conflicts->isEmpty()) {
            return null;
        }

        $firstDate = (string) $conflicts->first()->date;

        return [
            'date' => $firstDate,
            'slot_names' => $conflicts
                ->where('date', $firstDate)
                ->pluck('slot_name')
                ->unique()
                ->values()
                ->all(),
        ];
    }
}
