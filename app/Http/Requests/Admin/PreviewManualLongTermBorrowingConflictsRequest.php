<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PreviewManualLongTermBorrowingConflictsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'teacher_name' => ['nullable', 'string', 'max:50'],
            'course_name' => ['nullable', 'string', 'max:100'],
            'day_of_week' => ['required', 'array', 'min:1'],
            'day_of_week.*' => ['integer', 'between:1,7'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'periods' => ['required', 'array', 'min:1'],
            'periods.*' => ['integer', 'min:1'],
            'periods_by_day' => ['nullable', 'array'],
            'periods_by_day.*' => ['array', 'min:1'],
            'periods_by_day.*.*' => ['integer', 'min:1'],
            'conflict_resolution' => ['prohibited'],
            'slot_resolutions' => ['prohibited'],
        ];
    }
}
