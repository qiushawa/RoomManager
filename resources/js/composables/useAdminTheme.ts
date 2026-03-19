import { ref, computed } from 'vue';

type AdminTheme = 'dark' | 'light';

const STORAGE_KEY = 'admin-theme';

// Module-level singleton — shared across all components
const theme = ref<AdminTheme>(
    (typeof localStorage !== 'undefined' &&
        (localStorage.getItem(STORAGE_KEY) as AdminTheme)) ||
        'dark',
);

export function useAdminTheme() {
    const isDark = computed(() => theme.value === 'dark');

    function setTheme(t: AdminTheme) {
        theme.value = t;
        localStorage.setItem(STORAGE_KEY, t);
    }

    function toggleTheme() {
        setTheme(theme.value === 'dark' ? 'light' : 'dark');
    }

    return { theme, isDark, setTheme, toggleTheme };
}
