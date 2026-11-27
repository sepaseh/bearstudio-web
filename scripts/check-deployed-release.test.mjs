import assert from 'node:assert/strict';
import { test } from 'node:test';
import { verifyDeployedRelease } from './check-deployed-release.mjs';

const options = {
    publicOrigin: 'https://public.example.test',
    adminOrigin: 'https://admin.example.test',
    releaseId: '19bee5a6ff9ee7be6f07b5b7a97cf988',
    indexing: 'off',
};

function deployment({ staleAdmin = false, indexing = 'off', adminError = 'UNAUTHENTICATED' } = {}) {
    const seen = [];
    const fetchResponse = async (url, init) => {
        seen.push(url.href);
        assert.equal(init.method, 'GET');
        assert.equal(init.credentials, 'omit');
        assert.equal(init.redirect, 'error');
        assert.equal(init.headers['Cache-Control'], 'no-cache');

        const admin = url.hostname === 'admin.example.test';
        const session = url.pathname === '/api/v1/auth/session';
        const sitemap = url.pathname === '/sitemap.xml';
        const status = session ? (admin ? 401 : 404) : (sitemap && indexing === 'off' ? 404 : 200);
        const contentType = session ? 'application/json' : (sitemap ? 'application/xml' : (url.pathname === '/robots.txt' ? 'text/plain' : 'text/html'));
        return new Response('', {
            status,
            headers: {
                'Content-Type': contentType,
                'X-BearStudio-Release': staleAdmin && admin ? '0'.repeat(32) : options.releaseId,
                ...(session && { 'X-Error-Code': admin ? adminError : 'NOT_FOUND' }),
            },
        });
    };
    return { fetchResponse, seen };
}

test('checks both origins and accepts the expected public indexing policy', async () => {
    for (const indexing of ['on', 'off']) {
        const { fetchResponse, seen } = deployment({ indexing });
        assert.equal(await verifyDeployedRelease({ ...options, indexing }, fetchResponse), 8);
        assert.ok(seen.includes('https://public.example.test/api/v1/auth/session'));
        assert.ok(seen.includes('https://admin.example.test/api/v1/auth/session'));
    }
});

test('a matching public release cannot hide a stale admin backend', async () => {
    const { fetchResponse } = deployment({ staleAdmin: true });
    await assert.rejects(verifyDeployedRelease(options, fetchResponse), /admin\.example\.test.*expected backend release/);
});

test('a generic 401 does not establish the admin API boundary', async () => {
    const { fetchResponse } = deployment({ adminError: '' });
    await assert.rejects(verifyDeployedRelease(options, fetchResponse), /admin\.example\.test.*expected Laravel API error/);
});

test('an unsafe or ambiguous origin is rejected before the first request', async () => {
    let requests = 0;
    const fetchResponse = async () => { requests++; };
    for (const publicOrigin of ['http://public.example.test', 'https://username@public.example.test', 'https://public.example.test/extra', options.adminOrigin]) {
        await assert.rejects(verifyDeployedRelease({ ...options, publicOrigin }, fetchResponse));
    }
    assert.equal(requests, 0);
});
