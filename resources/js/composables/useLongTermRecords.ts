import type { LongTermRecord, PaginatedData } from '@/types';
import { withBase } from '@/utils';
import { onBeforeUnmount, onMounted, ref } from 'vue';

export function requestError(error: unknown): string {
    const response = (
        error as {
            response?: {
                data?: { errors?: Record<string, string[]>; message?: string };
            };
        }
    ).response?.data;
    return (
        Object.values(response?.errors ?? {})
            .flat()
            .join('；') ||
        response?.message ||
        '操作失敗，請稍後重試。'
    );
}

export function useLongTermRecords(defaultSemesterId: number | null) {
    const url = new URL(window.location.href);
    const filters = ref({
        semester_id:
            url.searchParams.get('record_semester') ??
            String(defaultSemesterId ?? ''),
        search: url.searchParams.get('record_search') ?? '',
        type: url.searchParams.get('record_type') ?? '',
        building: url.searchParams.get('record_building') ?? '',
        classroom_id: url.searchParams.get('record_classroom') ?? '',
        day_of_week: url.searchParams.get('record_day') ?? '',
    });
    const records = ref<PaginatedData<LongTermRecord> | null>(null);
    const loading = ref(false);
    const error = ref('');
    let requestId = 0;
    onBeforeUnmount(() => {
        requestId++;
    });
    async function load(page = 1) {
        const id = ++requestId;
        loading.value = true;
        error.value = '';
        const query = new URL(window.location.href);
        const keys = [
            'semester',
            'search',
            'type',
            'building',
            'classroom',
            'day',
        ];
        Object.values(filters.value).forEach((value, index) =>
            query.searchParams.set(`record_${keys[index]}`, value),
        );
        query.searchParams.set('record_page', String(page));
        query.searchParams.set('mode', 'records');
        window.history.replaceState(window.history.state, '', query);
        try {
            const response = await window.axios.get<
                PaginatedData<LongTermRecord>
            >(withBase('/admin/long-term-borrowing/records'), {
                params: { ...filters.value, page },
            });
            if (id === requestId) records.value = response.data;
        } catch (cause) {
            if (id === requestId) error.value = requestError(cause);
        } finally {
            if (id === requestId) loading.value = false;
        }
    }
    onMounted(() => load(Number(url.searchParams.get('record_page')) || 1));
    return { filters, records, loading, error, load };
}
