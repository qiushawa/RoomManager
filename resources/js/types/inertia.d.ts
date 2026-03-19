declare module '@inertiajs/core' {
    interface PageProps {
        flash?: {
            success?: string;
            error?: string;
            [key: string]: unknown;
        };
    }
}

export {};
