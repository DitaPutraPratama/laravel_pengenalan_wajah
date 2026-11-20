// JSON client for /api/face/*. Resolves to { ok, status, data } or { ok: false, code, retryAfter }.

export async function post(path, body) {
    let response;
    try {
        response = await fetch(`/api/face/${path}`, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                // The endpoints sit in Laravel's web group (session login), so CSRF applies.
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify(body),
        });
    } catch {
        return { ok: false, code: 'server_error' };
    }

    const json = await response.json().catch(() => ({}));
    if (response.ok) return { ok: true, status: response.status, data: json.data };

    return {
        ok: false,
        status: response.status,
        code: json.error?.code ?? 'server_error',
        retryAfter: json.error?.details?.retry_after,
    };
}
