<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminClassroomController extends Controller
{
    public function rooms()
    {
        $classrooms = Classroom::orderBy('code')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'name' => $c->name,
                'is_active' => (bool) $c->is_active,
                'bookings_count' => $c->bookings()->count(),
            ]);

        return Inertia::render('Admin/Rooms', [
            'classrooms' => $classrooms,
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

    public function destroyRoom(Classroom $classroom)
    {
        if ($classroom->bookings()->exists()) {
            return back()->withErrors(['classroom' => '此教室有關聯的借用紀錄，無法刪除。']);
        }

        $classroom->delete();

        return back()->with('success', '教室已刪除。');
    }
}
