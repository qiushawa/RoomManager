<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualLongTermBorrowingRequest extends FormRequest
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
            'teacher_name' => ['required', 'string', 'max:50'],
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
            'conflict_resolution' => ['nullable', 'array'],
            'conflict_resolution.approved_short_term' => ['nullable', 'string', 'in:keep_short_term'],
            'conflict_resolution.pending_short_term' => ['nullable', 'string', 'in:review_pending,reject_and_override'],
            'slot_resolutions' => ['nullable', 'array'],
            'slot_resolutions.*' => ['nullable', 'string', 'in:cancel_slot,review_pending,reject_and_override,defer_to_short_term,override_with_long_term'],
        ];
    }
}
