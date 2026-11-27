import { pathToFileURL } from 'node:url';

function httpsOrigin(value, label) {
    let url;
    try {
        url = new URL(value);
    } catch {
        throw new Error(`${label} must be an HTTPS origin.`);
    }

    if (url.protocol !== 'https:' || url.username || url.password || url.href !== `${url.origin}/`) {
        throw new Error(`${label} must be an HTTPS origin without credentials, path, query, or fragment.`);
    }

    return url;
}

export async function verifyDeployedRelease({ publicOrigin, adminOrigin, releaseId, indexing }, fetchResponse = fetch) {
    const publicUrl = httpsOrigin(publicOrigin, 'Public host');
    const adminUrl = httpsOrigin(adminOrigin, 'Admin host');
    if (publicUrl.hostname === adminUrl.hostname) {
        throw new Error('Public and admin hosts must have different hostnames.');
    }
    if (!/^[a-f0-9]{32}$/.test(releaseId)) {
        throw new Error('Provide the expected identifier from the private package release-id file.');
    }
    if (indexing !== 'on' && indexing !== 'off') {
        throw new Error('State the intended indexing mode with on or off.');
    }

    const checks = [
        [publicUrl, '/', 200, /^text\/html\b/i],
        [publicUrl, '/projects', 200, /^text\/html\b/i],
        [publicUrl, '/services', 200, /^text\/html\b/i],
        [publicUrl, '/articles', 200, /^text\/html\b/i],
        [publicUrl, '/robots.txt', 200, /^text\/plain\b/i],
        [publicUrl, '/sitemap.xml', indexing === 'on' ? 200 : 404, indexing === 'on' ? /^application\/xml\b/i : null],
        [publicUrl, '/api/v1/auth/session', 404, /^application\/json\b/i, 'NOT_FOUND'],
        [adminUrl, '/api/v1/auth/session', 401, /^application\/json\b/i, 'UNAUTHENTICATED'],
    ];

    for (const [origin, path, status, contentType, errorCode] of checks) {
        const url = new URL(path, origin);
        const response = await fetchResponse(url, {
            method: 'GET',
            headers: { Accept: 'application/json, text/html, */*', 'Cache-Control': 'no-cache' },
            credentials: 'omit',
            redirect: 'error',
            signal: AbortSignal.timeout(10_000),
        }).catch(() => { throw new Error(`GET ${url.href} failed or redirected.`); });

        try {
            if (response.status !== status) {
                throw new Error(`GET ${url.href} returned ${response.status}, expected ${status}.`);
            }
            if (response.headers.get('x-bearstudio-release') !== releaseId) {
                throw new Error(`GET ${url.href} did not identify the expected backend release.`);
            }
            if (contentType && !contentType.test(response.headers.get('content-type') ?? '')) {
                throw new Error(`GET ${url.href} returned an unexpected content type.`);
            }
            if (errorCode && response.headers.get('x-error-code') !== errorCode) {
                throw new Error(`GET ${url.href} did not return the expected Laravel API error.`);
            }
        } finally {
            await response.body?.cancel().catch(() => {});
        }
    }

    return checks.length;
}

if (process.argv[1] && import.meta.url === pathToFileURL(process.argv[1]).href) {
    const [publicOrigin, adminOrigin, releaseId, indexing, ...extra] = process.argv.slice(2);
    try {
        if (extra.length || !indexing) {
            throw new Error('Usage: node scripts/check-deployed-release.mjs <public HTTPS origin> <admin HTTPS origin> <release ID> <on|off>');
        }
        const checked = await verifyDeployedRelease({ publicOrigin, adminOrigin, releaseId, indexing });
        console.log(`Verified release ${releaseId} on both hosts across ${checked} HTTP responses.`);
    } catch (error) {
        console.error(error.message);
        process.exitCode = 1;
    }
}
