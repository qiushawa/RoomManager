import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

export function useConfirmDelete<T extends { id: number }>() {
    const deleteTarget = ref<T | null>(null);

    const confirmDelete = (item: T) => {
        deleteTarget.value = item;
    };

    const cancelDelete = () => {
        deleteTarget.value = null;
    };

    const doDelete = (
        endpoint: string | ((item: T) => string),
        onSuccess?: () => void,
    ) => {
        if (!deleteTarget.value) return;

        const url = typeof endpoint === 'function'
            ? endpoint(deleteTarget.value)
            : endpoint;

        router.delete(url, {
            preserveScroll: true,
            onSuccess: () => {
                cancelDelete();
                onSuccess?.();
            },
        });
    };

    return {
        deleteTarget,
        confirmDelete,
        cancelDelete,
        doDelete,
    };
}
