<x-mail::message>
@php
	$classroomName = trim($booking->classroom->code . ' ' . $booking->classroom->name);
@endphp

# 教室借用申請已送出

親愛的 **{{ $booking->borrower->name }}** 您好，

我們已收到您的教室借用申請，以下為本次申請摘要。

<x-mail::panel>
<div style="font-size: 15px; line-height: 1.8; color: #1f2937;">
	<div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">{{ $classroomName }}</div>
	<div><strong>借用日期：</strong>{{ $dateSummary['summary'] ?: '-' }}</div>
	<div><strong>申請節數：</strong>共 {{ $totalSlotCount }} 節</div>
	<div><strong>申請狀態：</strong><span style="color: #b45309; font-weight: 700;">待審核</span></div>
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

@if (! empty($cancelUrl))
<x-mail::button :url="$cancelUrl" color="error">
取消申請
</x-mail::button>

如需撤回這筆借用申請，請點擊上方按鈕進入確認頁面。
@endif

## 注意事項

- 管理員完成審核後，系統會再寄送結果通知。
- 若申請內容需要調整，請重新提出申請或聯繫系辦協助處理。
- 如有任何問題，請直接聯繫系辦公室。

感謝您的使用。
</x-mail::message>
