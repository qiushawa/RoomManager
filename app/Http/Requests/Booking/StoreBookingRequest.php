<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
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
            'classroom_id' => 'required|exists:classrooms,id',
            'classroom_code' => 'required|string',
            'date' => 'nullable|date|required_without:selections',
            'time_slot_ids' => 'nullable|array|min:1|required_with:date',
            'time_slot_ids.*' => 'exists:time_slots,id',
            'selections' => 'nullable|array|min:1|required_without:date',
            'selections.*.date' => 'required|date',
            'selections.*.time_slot_ids' => 'required|array|min:1',
            'selections.*.time_slot_ids.*' => 'exists:time_slots,id',
            'applicant.name' => 'required|string|max:50',
            'applicant.identity_code' => ['required', 'string', 'max:8', 'regex:/^[A-Za-z0-9]+$/'],
            'applicant.email' => 'required|email|max:255',
            'applicant.phone' => 'nullable|string|max:10',
            'applicant.department' => 'nullable|string|max:50',
            'applicant.teacher' => 'nullable|string|max:50',
            'applicant.reason' => 'nullable|string|max:255',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'applicant.identity_code.regex' => '學號/員工編號僅可輸入英文與數字。',
        ];
    }
}
