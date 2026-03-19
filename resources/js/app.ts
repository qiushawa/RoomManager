import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import './bootstrap';

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    resolve: async (name) => {
        const page = pages[`./Pages/${name}.vue`];

        if (!page) {
            throw new Error(`Page not found: ${name}`);
        }

        return (await page()) as any;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
});
