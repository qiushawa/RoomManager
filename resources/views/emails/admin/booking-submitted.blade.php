<x-mail::message>
@php
	$classroomName = trim($booking->classroom->code . ' ' . $booking->classroom->name);
@endphp

# 新的教室借用申請

系統收到一筆新的教室借用申請，以下為申請摘要：

<x-mail::panel>
<div style="font-size: 15px; line-height: 1.8; color: #1f2937;">
	<div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">{{ $classroomName }}</div>
	<div><strong>申請人：</strong>{{ $booking->borrower->name }} ({{ $booking->borrower->email }})</div>
	<div><strong>借用日期：</strong>{{ $dateSummary['summary'] ?: '-' }}</div>
	<div><strong>申請節數：</strong>共 {{ $totalSlotCount }} 節</div>
	<div><strong>申請事由：</strong>{{ $booking->purpose ?? '無' }}</div>
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

<x-mail::button :url="url('/admin/login')" color="primary">
前往後台審核
</x-mail::button>
</x-mail::message>
