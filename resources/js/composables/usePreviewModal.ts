import { ref } from 'vue';

export function usePreviewModal<T>() {
    const previewOpen = ref(false);
    const previewItem = ref<T | null>(null);

    const openPreview = (item: T) => {
        previewItem.value = item;
        previewOpen.value = true;
    };

    const closePreview = () => {
        previewOpen.value = false;
    };

    return {
        previewOpen,
        previewItem,
        openPreview,
        closePreview,
    };
}
