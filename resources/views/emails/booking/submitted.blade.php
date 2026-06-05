<x-mail::message>
@php
	$formattedDate = \Carbon\Carbon::parse($booking->date)->format('Y年m月d日');
	$classroomName = trim($booking->classroom->code . ' ' . $booking->classroom->name);
@endphp

# 教室借用申請已送出

親愛的 **{{ $booking->borrower->name }}** 您好，

我們已收到您的教室借用申請，以下為本次申請摘要。

<x-mail::panel>
<div style="font-size: 15px; line-height: 1.8; color: #1f2937;">
	<div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">{{ $classroomName }}</div>
	<div><strong>借用日期：</strong>{{ $formattedDate }}</div>
	<div><strong>申請時段：</strong>{{ $timeSlotSummary }}</div>
	<div><strong>申請狀態：</strong><span style="color: #b45309; font-weight: 700;">待審核</span></div>
</div>
</x-mail::panel>

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
