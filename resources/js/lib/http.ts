/*
 * JSON requests that carry Laravel's CSRF token. The XSRF-TOKEN cookie is
 * refreshed on every response, so it stays valid across sign-in and Inertia
 * visits (a token baked into the page would go stale).
 */
function xsrfToken() {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

export async function postJson<T = unknown>(url: string, body: unknown): Promise<{ ok: boolean; status: number; data: T }> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    let data: T;
    try {
        data = await response.json();
    } catch {
        data = {} as T;
    }
    return { ok: response.ok, status: response.status, data };
}
