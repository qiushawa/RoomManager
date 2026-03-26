import axios from 'axios';

declare global {
    interface Window {
        axios: typeof axios;
    }

    const __APP_VERSION__: string;
}

export {};
