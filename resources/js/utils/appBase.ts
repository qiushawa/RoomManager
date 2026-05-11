/**
 * APP_URL base path helpers for frontend routing and API calls.
 */

const APP_URL_META_NAME = 'app-url';

export function getAppUrl(): string {
    if (typeof document === 'undefined') return '';
    const meta = document.querySelector(`meta[name="${APP_URL_META_NAME}"]`) as HTMLMetaElement | null;
    return (meta?.content || '').trim();
}

export function getAppBasePath(): string {
    if (typeof window === 'undefined') return '';
    const appUrl = getAppUrl();
    if (!appUrl) return '';
    try {
        const url = new URL(appUrl, window.location.origin);
        const path = url.pathname || '';
        if (path === '/' || path === '') return '';
        return path.endsWith('/') ? path.slice(0, -1) : path;
    } catch {
        return '';
    }
}

export function withBase(path: string): string {
    if (!path) return path;
    if (/^(https?:)?\/\//.test(path) || path.startsWith('mailto:') || path.startsWith('tel:')) {
        return path;
    }

    const base = getAppBasePath();
    if (!base) return path;

    if (path === base || path.startsWith(`${base}/`)) return path;

    if (path.startsWith('/')) return `${base}${path}`;

    const joined = `${base}/${path}`;
    return joined.replace(/\/+/g, '/');
}
