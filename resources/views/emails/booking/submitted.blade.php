<x-mail::message>
@php
	$formattedDate = \Carbon\Carbon::parse($booking->date)->format('Y年m月d日');
	$classroomName = trim($booking->classroom->code . ' ' . $booking->classroom->name);
@endphp

# 教室借用申請已送出

親愛的 **{{ $booking->borrower->name }}** 您好，

我們已收到您的教室借用申請，案件目前狀態為 **待審核**。以下為本次申請摘要，方便您再次確認。

<x-mail::panel>
<div style="font-size: 15px; line-height: 1.8; color: #1f2937;">
	<div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">{{ $classroomName }}</div>
	<div><strong>借用日期：</strong>{{ $formattedDate }}</div>
	<div><strong>申請時段：</strong>{{ implode('、', $timeSlots) }}</div>
	<div><strong>申請狀態：</strong><span style="color: #b45309; font-weight: 700;">待審核</span></div>
</div>
</x-mail::panel>

## 申請資訊

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 12px 0 20px; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
	<tr>
		<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-bottom: 1px solid #e5e7eb;">申請人</td>
		<td style="padding: 12px 16px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $booking->borrower->name }}</td>
	</tr>
	<tr>
		<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-bottom: 1px solid #e5e7eb;">教室</td>
		<td style="padding: 12px 16px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $classroomName }}</td>
	</tr>
	<tr>
		<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-bottom: 1px solid #e5e7eb;">日期</td>
		<td style="padding: 12px 16px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $formattedDate }}</td>
	</tr>
	<tr>
		<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-bottom: 1px solid #e5e7eb;">指導老師</td>
		<td style="padding: 12px 16px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $booking->teacher ?? '未填寫' }}</td>
	</tr>
	<tr>
		<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; vertical-align: top;">借用事由</td>
		<td style="padding: 12px 16px; color: #111827;">{{ $booking->reason ?? '未填寫' }}</td>
	</tr>
</table>

@if (! empty($timeSlotDetails))
## 申請時段

<div style="margin: 12px 0 8px; font-size: 13px; color: #6b7280;">已選 {{ count($timeSlotDetails) }} 節</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0 0 20px; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; background: #ffffff;">
	<tbody>
		<tr>
			<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">節次</td>
			@foreach ($timeSlotDetails as $timeSlot)
			<td style="padding: 12px 16px; color: #111827; font-weight: 600; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb; text-align: center;">{{ $timeSlot['name'] }}</td>
			@endforeach
		</tr>
		<tr>
			<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-right: 1px solid #e5e7eb;">時間</td>
			@foreach ($timeSlotDetails as $timeSlot)
			<td style="padding: 12px 16px; color: #111827; border-right: 1px solid #e5e7eb; text-align: center;">{{ $timeSlot['start_time'] }} - {{ $timeSlot['end_time'] }}</td>
			@endforeach
		</tr>
	</tbody>
</table>

<!-- <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 0 0 20px; border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; background: #ffffff;">
	<tr>
		<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-bottom: 1px solid #e5e7eb;">借用日期</td>
		<td style="padding: 12px 16px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $formattedDate }}</td>
	</tr>
	<tr>
		<td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600;">時段摘要</td>
		<td style="padding: 12px 16px; color: #111827;">{{ implode('、', $timeSlots) }}</td>
	</tr>
</table> -->
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
