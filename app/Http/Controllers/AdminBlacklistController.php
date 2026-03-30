<?php

namespace App\Http\Controllers;

use App\Models\Blacklist;
use App\Models\BlacklistReason;
use App\Models\Borrower;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminBlacklistController extends Controller
{
    public function users(Request $request)
    {
        $query = Blacklist::with([
            'borrower:id,identity_code,name,department',
            'blacklistDetails.reason:id,reason',
        ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->whereHas('borrower', function ($q) use ($search) {
                $q->where('identity_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $blacklists = $query
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(function ($blacklist) {
                return [
                    'id' => $blacklist->id,
                    'borrower_identity_code' => $blacklist->borrower?->identity_code,
                    'borrower_name' => $blacklist->borrower?->name,
                    'borrower_department' => $blacklist->borrower?->department,
                    'banned_until' => $blacklist->banned_until?->format('Y-m-d'),
                    'reasons' => $blacklist->blacklistDetails
                        ->map(fn ($detail) => $detail->reason?->reason)
                        ->filter()
                        ->values()
                        ->all(),
                ];
            });

        return Inertia::render('Admin/Blacklist', [
            'blacklists' => $blacklists,
            'blacklistReasons' => BlacklistReason::query()
                ->orderBy('id')
                ->get(['id', 'reason']),
            'defaultBannedUntil' => $this->resolveDefaultBlacklistEndDate(),
            'storeBlacklistUrl' => route('admin.users.blacklist.store'),
            'filters' => $request->only(['search']),
        ]);
    }

    public function storeBlacklist(Request $request)
    {
        $validated = $request->validate([
            'identity_code' => ['required', 'string', 'exists:borrowers,identity_code'],
            'reason_ids' => ['required', 'array', 'min:1'],
            'reason_ids.*' => ['integer', 'distinct', 'exists:blacklist_reasons,id'],
            'banned_until' => ['nullable', 'date'],
        ]);

        $borrower = Borrower::query()
            ->where('identity_code', $validated['identity_code'])
            ->firstOrFail();

        $bannedUntilDate = (string) ($validated['banned_until'] ?? $this->resolveDefaultBlacklistEndDate());
        $reasonIds = collect($validated['reason_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        DB::transaction(function () use ($borrower, $bannedUntilDate, $reasonIds): void {
            $blacklist = Blacklist::create([
                'borrower_id' => $borrower->id,
                'banned_until' => $bannedUntilDate . ' 23:59:59',
            ]);

            $blacklist->blacklistDetails()->createMany(
                $reasonIds->map(fn ($reasonId) => ['reason_id' => $reasonId])->all()
            );
        });

        return back()->with('success', '黑名單已新增。');
    }

    private function resolveDefaultBlacklistEndDate(): string
    {
        $semester = Semester::findByDate(now())
            ?? Semester::query()
                ->whereDate('end_date', '>=', now()->toDateString())
                ->orderBy('end_date')
                ->first();

        return $semester?->end_date?->format('Y-m-d') ?? now()->toDateString();
    }
}
