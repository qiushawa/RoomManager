/// <reference types="vite/client" />
import { DefineComponent } from 'vue';

declare module '*.vue' {
    const component: DefineComponent<
        Record<string, unknown>,
        Record<string, unknown>,
        any
    >;
    export default component;
}
