<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    public function settings()
    {
        return Inertia::render('Admin/Settings', [
            'currentSemester' => Semester::findByDate(now())?->display_name,
            'semesters' => Semester::query()
                ->orderByDesc('start_date')
                ->get()
                ->map(fn ($semester) => [
                    'id' => $semester->id,
                    'academic_year' => $semester->academic_year,
                    'semester' => $semester->semester,
                    'display_name' => $semester->display_name,
                    'start_date' => $semester->start_date?->format('Y-m-d'),
                    'end_date' => $semester->end_date?->format('Y-m-d'),
                ])
                ->values(),
        ]);
    }

    public function storeSemester(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'integer', 'min:1', 'max:999'],
            'semester' => ['required', 'integer', 'in:1,2'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $existsSameTerm = Semester::query()
            ->where('academic_year', $validated['academic_year'])
            ->where('semester', $validated['semester'])
            ->exists();

        if ($existsSameTerm) {
            return back()->withErrors([
                'semester' => '相同學年與學期已存在。',
            ]);
        }

        $overlapping = Semester::overlapping($validated['start_date'], $validated['end_date']);
        if ($overlapping->isNotEmpty()) {
            return back()->withErrors([
                'start_date' => '日期區間與既有學期重疊，請調整起訖日期。',
            ]);
        }

        Semester::create($validated);

        return back()->with('success', '學期資料已新增。');
    }
}
