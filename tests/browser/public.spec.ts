import { expect, test } from '@playwright/test';

test('public pages render, navigate and expose metadata', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', error => errors.push(error.message));
    for (const route of ['/', '/projects', '/services', '/articles']) {
        const response = await page.goto(route);
        expect(response?.status()).toBe(200);
        await expect(page.locator('main#main-content')).toBeVisible();
        await expect(page.locator('link[rel=canonical]')).toHaveAttribute('href', `http://127.0.0.1:8765${route}`);
        const data = await page.locator('script[type="application/ld+json"]').textContent();
        expect(JSON.parse(data || '')).toBeTruthy();
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    }
    expect(errors).toEqual([]);
});

test('theme and keyboard navigation work with reduced motion', async ({ page }, testInfo) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await page.goto('/');
    const before = await page.locator('html').getAttribute('class');
    await page.getByRole('button', { name: /Switch to (light|dark) theme/ }).click();
    expect(await page.locator('html').getAttribute('class')).not.toBe(before);
    if (testInfo.project.name === 'mobile') {
        await page.getByRole('button', { name: 'Open navigation menu' }).click();
        await expect(page.locator('#menu-dialog')).toBeVisible();
        await page.keyboard.press('Escape');
        await expect(page.locator('#menu-dialog')).not.toBeVisible();
        await expect(page.getByRole('button', { name: 'Open navigation menu' })).toBeFocused();
    }
    await page.goto('/');
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();
});

test('discovery and administrative host boundary use real HTTP', async ({ request }) => {
    expect((await request.get('/sitemap.xml')).status()).toBe(200);
    expect((await request.get('/api/v1/auth/session')).status()).toBe(404);
    const response = await request.get('/api/v1/auth/session', { headers: { Host: 'localhost' } });
    expect(response.status()).toBe(401);
    expect(response.headers()['x-error-code']).toBe('UNAUTHENTICATED');
});
