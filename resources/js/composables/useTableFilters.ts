import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

interface UseTableFiltersOptions {
    route: string;
    initialSearch?: string;
    initialStatus?: string;
    includeStatus?: boolean;
}

export function useTableFilters(options: UseTableFiltersOptions) {
    const searchInput = ref(options.initialSearch ?? '');
    const filterStatus = ref(options.initialStatus ?? 'all');

    const applyFilters = () => {
        const payload: Record<string, string | undefined> = {
            search: searchInput.value || undefined,
        };

        if (options.includeStatus !== false) {
            payload.status = filterStatus.value !== 'all' ? filterStatus.value : undefined;
        }

        router.get(options.route, payload, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const setStatusAndApply = (value: string) => {
        filterStatus.value = value;
        applyFilters();
    };

    return {
        searchInput,
        filterStatus,
        applyFilters,
        setStatusAndApply,
    };
}
