<x-mail::message>
@php
    $classroomName = trim(($booking->classroom->code ?? '') . ' ' . ($booking->classroom->name ?? ''));
@endphp

# 教室借用審核結果通知

親愛的 **{{ $booking->borrower->name ?? '申請人' }}** 您好，

您的教室借用申請已完成審核，結果為：

<x-mail::panel>
<div style="font-size: 15px; line-height: 1.8; color: #1f2937;">
    <div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">{{ $classroomName !== '' ? $classroomName : '教室資訊' }}</div>
    <div><strong>借用日期：</strong>{{ $dateSummary['summary'] ?: '-' }}</div>
    <div><strong>申請節數：</strong>共 {{ $totalSlotCount }} 節</div>
    <div><strong>審核結果：</strong><span style="color: {{ $statusColor }}; font-weight: 700;">{{ $statusLabel }}</span></div>
</div>
</x-mail::panel>

@if (! empty($dateSchedules))
## 借用日期與節數明細

@foreach ($dateSchedules as $schedule)
**{{ $schedule['date'] }}**｜第 {{ $schedule['slot_summary'] }} 節

@if (! empty($schedule['slots']))
<!-- <div style="margin: 4px 0 16px; font-size: 13px; color: #6b7280;">
@foreach ($schedule['slots'] as $slot)
第 {{ $slot['name'] }} 節（{{ $slot['start_time'] }}–{{ $slot['end_time'] }}）@if (! $loop->last)、@endif
@endforeach
</div> -->
@endif
@endforeach
@endif

如有疑問，請聯繫系辦公室協助處理。
</x-mail::message>
