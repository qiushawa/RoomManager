<x-mail::message>
# 您的教室借用申請已送出

親愛的 **{{ $booking->borrower->name }}** 您好，

您的教室借用申請已成功送出，目前狀態為「待審核」。以下是您的申請資訊：

<x-mail::table>
| 項目 | 內容 |
|:-----|:-----|
| 教室 | {{ $booking->classroom->code }} {{ $booking->classroom->name }} |
| 日期 | {{ \Carbon\Carbon::parse($booking->date)->format('Y年m月d日') }} |
| 時段 | {{ implode('、', $timeSlots) }} |
| 指導老師 | {{ $booking->teacher ?? '未填寫' }} |
| 借用事由 | {{ $booking->reason ?? '未填寫' }} |
</x-mail::table>

## 注意事項
- 請耐心等待管理員審核，審核結果將另行通知。
- 如有任何問題，請聯繫系辦公室。

<x-mail::button :url="config('app.url') . '/bookings/search'">
查詢借用紀錄
</x-mail::button>

</x-mail::message>
