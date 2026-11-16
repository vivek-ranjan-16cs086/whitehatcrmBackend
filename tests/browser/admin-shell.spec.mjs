import {test, expect} from '@playwright/test';
import {readFileSync} from 'node:fs';

test.use({channel: 'chrome'});

async function setup(page) {
    const layout = readFileSync('resources/views/layouts/admin.blade.php', 'utf8');
    const brand = layout.match(/<a class="topbar-brand"[\s\S]*?<\/a>/)[0].replace(/{{[\s\S]*?}}/g, '#');
    const toggle = layout.match(/<button class="btn[^>]*topbar-hamburger"[\s\S]*?<\/button>/)[0];
    const mobileControls = layout.match(/<div class="sidebar-mobile-controls[\s\S]*?<\/div>/)[0];
    await page.setContent(`<body><aside class="sidebar" id="sidebar">${mobileControls}<div id="dynamicSidebar"><ul class="sidebar-nav"><li class="sidebar-nav-heading">Overview</li><li class="nav-item"><a href="#">Dashboard</a></li><li class="nav-item"><a href="#">User Management</a></li></ul></div></aside><header class="topbar">${toggle}${brand}<div class="topbar-left"><div class="page-title">Dashboard</div></div><div class="topbar-right"><button class="notification-btn">Chat</button><button class="notification-btn">Bell</button></div></header><main class="main-content">Content</main></body>`);
    for (const path of ['public/admin/libraries/css/bootstrap.min.css', 'public/admin/css/admin.css', 'public/admin/css/theme.css']) await page.addStyleTag({path});
    for (const name of ['toggleSidebar', 'closeSidebar']) {
        await page.addScriptTag({content: layout.match(new RegExp('function ' + name + '\\(\\)[\\s\\S]*?\\n        \\}'))[0]});
    }
}

test('brand shares the desktop header and navigation begins immediately below it', async ({page}) => {
    await page.setViewportSize({width: 1440, height: 900});
    await setup(page);
    const header = await page.locator('.topbar').boundingBox();
    const brand = await page.locator('.topbar-brand').boundingBox();
    const sidebar = await page.locator('.sidebar').boundingBox();
    const firstLink = await page.getByRole('link', {name: 'Dashboard', exact: true}).boundingBox();
    expect(header.x).toBe(0);
    expect(header.width).toBe(1440);
    expect(brand.y).toBe(header.y);
    expect(Math.abs(brand.height - header.height)).toBeLessThanOrEqual(1);
    expect(sidebar.y).toBe(header.height);
    expect(firstLink.y - sidebar.y).toBeLessThan(55);
    await expect(page.locator('.sidebar .sidebar-brand')).toHaveCount(0);
});

test('mobile header fits and menu controls open and close navigation', async ({page}) => {
    await page.setViewportSize({width: 375, height: 800});
    await setup(page);
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBe(375);
    await expect(page.locator('.topbar-brand')).toBeVisible();
    await page.getByRole('button', {name: 'Toggle sidebar', exact: true}).click();
    await expect(page.locator('.sidebar')).toHaveClass(/show/);
    await page.getByRole('button', {name: 'Close sidebar', exact: true}).click();
    await expect(page.locator('.sidebar')).not.toHaveClass(/show/);
});
