/**
 * APP_URL base path helpers for frontend routing and API calls.
 */

const APP_URL_META_NAME = 'app-url';

let cachedAppUrl: string | null = null;
let hasCachedAppUrl = false;

let cachedAppBasePath: string | null = null;
let hasCachedAppBasePath = false;

export function getAppUrl(): string {
    if (!hasCachedAppUrl) {
        if (typeof document === 'undefined') {
            cachedAppUrl = '';
        } else {
            const meta = document.querySelector(`meta[name="${APP_URL_META_NAME}"]`) as HTMLMetaElement | null;
            cachedAppUrl = (meta?.content || '').trim();
        }
        hasCachedAppUrl = true;
    }

    return cachedAppUrl ?? '';
}

export function getAppBasePath(): string {
    if (!hasCachedAppBasePath) {
        if (typeof window === 'undefined') {
            cachedAppBasePath = '';
        } else {
            const appUrl = getAppUrl();
            if (!appUrl) {
                cachedAppBasePath = '';
            } else {
                try {
                    const url = new URL(appUrl, window.location.origin);
                    const path = url.pathname || '';
                    if (path === '/' || path === '') {
                        cachedAppBasePath = '';
                    } else {
                        cachedAppBasePath = path.endsWith('/') ? path.slice(0, -1) : path;
                    }
                } catch {
                    cachedAppBasePath = '';
                }
            }
        }
        hasCachedAppBasePath = true;
    }

    return cachedAppBasePath ?? '';
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
