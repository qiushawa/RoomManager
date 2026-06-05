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
如有疑問，請聯繫系辦公室協助處理。
</x-mail::message>