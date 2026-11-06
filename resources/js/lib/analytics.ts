/*
 * Google Analytics 4. The snippet in app.blade.php loads gtag only when
 * GOOGLE_ANALYTICS_ID is set, so with no id nothing here does anything —
 * every call below is a no-op and the site behaves exactly as before.
 *
 * Page views are sent by hand (the snippet turns the automatic one off):
 * Inertia replaces the page without a browser load, and the reader turns
 * pages with history.replaceState, which gtag would never notice.
 */

type Params = Record<string, unknown>;

declare global {
    interface Window {
        dataLayer?: unknown[];
        gtag?: (...args: unknown[]) => void;
    }
}

/** An event the server asked us to report, shared on the Inertia page. */
export interface ServerEvent {
    name: string;
    params?: Params;
}

export function track(name: string, params: Params = {}): void {
    window.gtag?.('event', name, params);
}

/** `url` is a path, as Inertia and the reader know it. */
export function pageView(url?: string, title?: string): void {
    track('page_view', {
        page_location: new URL(url ?? window.location.href, window.location.origin).href,
        page_title: title ?? document.title,
    });
}

let lastReported = '';

/**
 * Report an Inertia page: its view, plus the one event the server flashed.
 * Called for the first page and on every visit after it, which can mean the
 * same page twice (the BLIK poll reloads it), so repeats are dropped.
 */
export function reportPage(page: { url: string; props: Record<string, unknown> }): void {
    const event = (page.props.analytics ?? null) as ServerEvent | null;
    const key = `${page.url}|${event ? JSON.stringify(event) : ''}`;
    if (key === lastReported) return;
    lastReported = key;

    const send = () => {
        pageView(page.url);
        if (event?.name) track(event.name, event.params ?? {});
    };

    // Inertia puts the new title in place a moment after the page itself, so
    // wait for it — and send anyway if it never changes (two pages can share
    // a title, and not every page sets one).
    const before = document.title;
    const started = Date.now();
    const whenTitled = () => {
        if (document.title !== before || Date.now() - started > 600) send();
        else setTimeout(whenTitled, 50);
    };
    setTimeout(whenTitled);
}
