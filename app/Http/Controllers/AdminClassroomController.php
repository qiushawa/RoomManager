<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminClassroomController extends Controller
{
    public function rooms(Request $request)
    {
        $query = Classroom::query()->withCount('bookings');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            if ($request->input('status') === 'enabled') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'disabled') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($builder) use ($search) {
                $builder->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $classrooms = $query->orderBy('code')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'is_active' => (bool) $c->is_active,
                'bookings_count' => $c->bookings_count,
            ]);

        return Inertia::render('Admin/Rooms', [
            'classrooms' => $classrooms,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function storeRoom(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:7|unique:classrooms,code',
            'name' => 'required|string|max:25',
        ]);

        Classroom::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return back()->with('success', '教室已新增。');
    }

    public function toggleRoom(Classroom $classroom)
    {
        $classroom->update(['is_active' => ! $classroom->is_active]);

        return back()->with('success', '教室狀態已更新。');
    }

    public function batchUpdateRooms(Request $request)
    {
        $validated = $request->validate([
            'action' => 'required|string|in:enable,disable,rename',
            'selected_ids' => 'required|array|min:1',
            'selected_ids.*' => 'integer|exists:classrooms,id',
            'name' => 'nullable|string|max:25',
        ]);

        $ids = collect($validated['selected_ids'])
            ->unique()
            ->values();

        if ($validated['action'] === 'rename') {
            if ($ids->count() !== 1) {
                return back()->withErrors(['operation' => '更改教室名稱僅支援單選。']);
            }

            $name = trim((string) ($validated['name'] ?? ''));
            if ($name === '') {
                return back()->withErrors(['name' => '請輸入教室名稱。']);
            }

            Classroom::query()->findOrFail($ids->first())->update([
                'name' => $name,
            ]);

            return back()->with('success', '教室名稱已更新。');
        }

        $isActive = $validated['action'] === 'enable';
        $affected = Classroom::query()
            ->whereIn('id', $ids->all())
            ->update(['is_active' => $isActive]);

        return back()->with('success', $isActive
            ? "已啟用 {$affected} 間教室。"
            : "已停用 {$affected} 間教室。");
    }

    public function destroyRoom(Classroom $classroom)
    {
        if ($classroom->bookings()->exists()) {
            return back()->withErrors(['classroom' => '此教室有關聯的借用紀錄，無法刪除。']);
        }

        $classroom->delete();

        return back()->with('success', '教室已刪除。');
    }
}
