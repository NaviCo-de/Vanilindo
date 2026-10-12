// Run only against the isolated local rehearsal with Playwright and a private fixture account.
const { chromium } = require('playwright');
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const output = process.env.AUDIT_OUTPUT || '/tmp/vanilindo-audit';
const credentials = JSON.parse(fs.readFileSync(path.join(output, 'browser-credentials.json'), 'utf8'));
const base = 'http://127.0.0.1:8177';

(async () => {
    const browser = await chromium.launch({ headless: true, channel: 'chrome' });
    const results = [];
    const errors = [];
    const context = await browser.newContext({ reducedMotion: 'reduce' });
    const page = await context.newPage();
    page.on('pageerror', error => errors.push(error.message));
    page.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
    try {
        for (const width of [1440, 390]) {
            await page.setViewportSize({ width, height: 900 });
            await page.goto(base, { waitUntil: 'networkidle' });
            await page.evaluate(() => document.fonts.ready);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'Horizontal page overflow');
            assert.equal(await page.locator('img').evaluateAll(images => images.filter(image => image.complete && !image.naturalWidth).length), 0, 'Broken image');
            const count = await page.locator('[data-blog-slide]').count();
            assert.ok(count >= 250, 'All blogs must remain available');
            await page.locator('[data-blog-track]').press('End');
            await page.waitForFunction(count => document.querySelector('[data-blog-status]').textContent === `${count} of ${count}`, count);
            await page.locator('[data-blog-track]').press('Home');
            if (width === 390) {
                await page.locator('.menu-toggle').click();
                assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'true');
                await page.keyboard.press('Escape');
                assert.equal(await page.locator('.menu-toggle').getAttribute('aria-expanded'), 'false');
            }
            await page.screenshot({ path: path.join(output, `public-${width}.png`), fullPage: true });
            results.push({ check: `public-${width}`, passed: true, blogSlides: count });
        }
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto(`${base}/admin/login`);
        await page.locator('input[type=email]').fill(credentials.email);
        await page.locator('input[type=password]').fill(credentials.password);
        await page.getByRole('button', { name: 'Sign in', exact: true }).click();
        await page.waitForURL(`${base}/admin`);
        results.push({ check: 'admin-login', passed: true });

        const title = `Browser audit ${Date.now()}`;
        await page.goto(`${base}/admin/articles/create`, { waitUntil: 'networkidle' });
        await page.locator('[id="form.title"]').fill(title);
        await page.locator('[id="form.external_url"]').fill('https://example.com/vanilindo-audit');
        await page.locator('[id="form.excerpt"]').fill('Browser-tested upload and publishing.');
        await page.locator('input[type=file]').setInputFiles(path.resolve('public/images/canva/quality.jpg'));
        await page.getByText('Upload complete', { exact: true }).waitFor({ timeout: 20000 });
        await page.getByRole('button', { name: 'Create', exact: true }).click();
        await page.waitForURL(url => !url.pathname.endsWith('/create') && url.pathname.startsWith('/admin/articles'), { timeout: 20000 });
        await page.screenshot({ path: path.join(output, 'admin-upload-created.png') });
        const publicPage = await context.newPage();
        await publicPage.goto(base, { waitUntil: 'networkidle' });
        const card = publicPage.locator('[data-blog-slide]').filter({ hasText: title });
        assert.equal(await card.count(), 1);
        assert.equal(await card.locator('a').getAttribute('href'), 'https://example.com/vanilindo-audit');
        const image = await card.locator('img').getAttribute('src');
        assert.ok(image.includes('/storage/articles/'));
        assert.equal((await context.request.get(image)).status(), 200);
        results.push({ check: 'admin-upload-publish-and-public-storage', passed: true });

        if (!page.url().endsWith('/edit')) {
            await page.getByRole('link', { name: 'Edit', exact: true }).first().click();
        }
        await page.locator('[id="form.excerpt"]').fill('Browser-tested update persisted.');
        await page.getByRole('button', { name: 'Save changes', exact: true }).click();
        await page.getByText('Saved', { exact: true }).waitFor();
        await publicPage.reload({ waitUntil: 'networkidle' });
        assert.ok(await publicPage.locator('[data-blog-slide]').filter({ hasText: title }).textContent().then(text => text.includes('Browser-tested update persisted.')));
        results.push({ check: 'admin-edit-existing-image-and-persist', passed: true });
        for (const route of ['/admin/products', '/admin/content-blocks', '/admin/site-settings/1/edit']) {
            const response = await page.goto(base + route, { waitUntil: 'networkidle' });
            assert.equal(response.status(), 200);
        }
        results.push({ check: 'all-admin-resources-render', passed: true });
        const guest = await browser.newContext();
        const login = await guest.newPage();
        await login.goto(`${base}/admin/login`);
        await login.locator('input[type=email]').fill('does-not-exist@example.test');
        await login.locator('input[type=password]').fill('invalid-test-password');
        for (let attempt = 0; attempt < 6; attempt++) {
            const response = login.waitForResponse(response => response.request().method() === 'POST' && response.url().includes('/livewire'));
            await login.getByRole('button', { name: 'Sign in', exact: true }).click();
            await response;
        }
        await login.getByText(/Too many login attempts/i).waitFor();
        results.push({ check: 'login-brute-force-throttle', passed: true });
        await guest.close();
        assert.deepEqual(errors, [], 'Browser errors or CSP violations');
        fs.writeFileSync(path.join(output, 'browser-smoke.json'), JSON.stringify({ passed: true, results, errors }, null, 2));
        console.log(JSON.stringify({ passed: true, results, errors }));
    } catch (error) {
        await page.screenshot({ path: path.join(output, 'browser-failure.png'), fullPage: true });
        fs.writeFileSync(path.join(output, 'browser-smoke.json'), JSON.stringify({ passed: false, results, errors, failure: error.message }, null, 2));
        throw error;
    } finally {
        await browser.close();
    }
})();
