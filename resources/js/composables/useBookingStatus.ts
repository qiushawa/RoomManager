const STATUS_LABELS = ['待審核', '已核准', '已拒絕', '已取消'] as const;

const STATUS_STYLES = [
    'bg-blue-500/15 text-blue-400 border border-blue-500/25',
    'bg-emerald-500/15 text-emerald-400 border border-emerald-500/25',
    'bg-red-500/15 text-red-400 border border-red-500/25',
    'bg-slate-500/15 text-slate-400 border border-slate-500/25',
] as const;

export function useBookingStatus() {
    const statusLabel = (status: number): string => STATUS_LABELS[status] ?? '未知';

    const statusStyle = (status: number): string => {
        return STATUS_STYLES[status] ?? 'bg-slate-500/15 text-slate-400';
    };

    return {
        statusLabel,
        statusStyle,
    };
}
