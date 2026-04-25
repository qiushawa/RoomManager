<x-mail::message>
@php
    $summary = $booking->getDateSummaryData('Y年m月d日', true);
    $classroomName = trim(($booking->classroom->code ?? '') . ' ' . ($booking->classroom->name ?? ''));
@endphp

# 教室借用審核結果通知

親愛的 **{{ $booking->borrower->name ?? '申請人' }}** 您好，

您的教室借用申請已完成審核，結果為：

<x-mail::panel>
<div style="font-size: 15px; line-height: 1.8; color: #1f2937;">
    <div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">{{ $classroomName !== '' ? $classroomName : '教室資訊' }}</div>
    <div><strong>借用日期：</strong>{{ $summary['summary'] ?: '-' }}</div>
    <div><strong>審核結果：</strong><span style="color: {{ $statusColor }}; font-weight: 700;">{{ $statusLabel }}</span></div>
</div>
</x-mail::panel>

## 申請資訊

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; margin: 12px 0 20px; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden;">
    <tr>
        <td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-bottom: 1px solid #e5e7eb;">申請人</td>
        <td style="padding: 12px 16px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $booking->borrower->name ?? '-' }}</td>
    </tr>
    <tr>
        <td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-bottom: 1px solid #e5e7eb;">教室</td>
        <td style="padding: 12px 16px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $classroomName !== '' ? $classroomName : '-' }}</td>
    </tr>
    <tr>
        <td style="width: 120px; padding: 12px 16px; background: #f9fafb; color: #4b5563; font-weight: 600; border-bottom: 1px solid #e5e7eb;">日期</td>
        <td style="padding: 12px 16px; color: #111827; border-bottom: 1px solid #e5e7eb;">{{ $summary['summary'] ?: '-' }}</td>
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

<div style="margin: 12px 0 8px; font-size: 13px; color: #6b7280;">共 {{ count($timeSlotDetails) }} 節</div>

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
@endif

如有疑問，請聯繫系辦公室協助處理。

感謝您的使用。
</x-mail::message>