<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            // 1=手動新增, 2=課表匯入
            $table->tinyInteger('source')->nullable()->after('borrow_type')->comment('1=手動新增, 2=課表匯入');
        });

        DB::table('course_schedules')
            ->select(['id', 'borrow_type'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('course_schedules')
                        ->where('id', $row->id)
                        ->update([
                            'source' => is_null($row->borrow_type) ? 2 : 1,
                        ]);
                }
            });

        $semesterRanges = DB::table('semesters')
            ->select(['id', 'start_date', 'end_date'])
            ->get()
            ->keyBy('id');

        DB::table('course_schedules')
            ->select(['id', 'semester_id', 'start_date', 'end_date'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($semesterRanges) {
                foreach ($rows as $row) {
                    $semester = $semesterRanges->get($row->semester_id);
                    if (!$semester) {
                        continue;
                    }

                    $newStartDate = $row->start_date ?: $semester->start_date;
                    $newEndDate = $row->end_date ?: $semester->end_date;

                    if ($newStartDate === $row->start_date && $newEndDate === $row->end_date) {
                        continue;
                    }

                    DB::table('course_schedules')
                        ->where('id', $row->id)
                        ->update([
                            'start_date' => $newStartDate,
                            'end_date' => $newEndDate,
                        ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('course_schedules', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
