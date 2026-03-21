<?php

namespace App\Http\Middleware;

use App\Models\Semester;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCurrentSemesterConfigured
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hasCurrentOrFutureSemester = Semester::query()
            ->whereDate('end_date', '>=', now()->toDateString())
            ->exists();

        if ($hasCurrentOrFutureSemester) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => '目前與未來皆無任何已知學期，請先於系統設定建立學期資料。',
            ], 409);
        }

        return redirect()
            ->route('admin.settings')
            ->with('error', '目前與未來皆無任何已知學期，請先填寫學期資料。');
    }
}
