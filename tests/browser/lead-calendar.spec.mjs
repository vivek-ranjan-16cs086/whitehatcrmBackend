import {test, expect} from '@playwright/test';
import {readFileSync} from 'node:fs';

test.use({channel: 'chrome'});

async function setup(page) {
    await page.clock.setFixedTime(new Date('2026-11-16T06:30:00Z'));
    const view = readFileSync('resources/views/admin/lead-management/index.blade.php', 'utf8');
    let markup = view.slice(view.indexOf('<div id="leadCalendarFilter"'), view.indexOf('<div class="lead-list-secondary"'));
    markup = markup.replace(/@foreach[\s\S]*?@endforeach/, Object.entries({today: 'Today', yesterday: 'Yesterday', last7: 'Last 7 Days', last30: 'Last 30 Days', month: 'This Month', lastMonth: 'Last Month', last3: 'Last 3 Months', last6: 'Last 6 Months'})
        .map(([key, label]) => `<button type="button" data-calendar-preset="${key}">${label}</button>`).join(''));
    await page.setContent('<body class="leads-management-page">' + markup + '</body>');
    for (const path of ['public/admin/libraries/css/bootstrap.min.css', 'public/admin/css/theme.css']) await page.addStyleTag({path});
    await page.addScriptTag({path: 'public/admin/libraries/js/jquery-3.7.1.min.js'});
    await page.evaluate(() => {
        window.appliedRanges = [];
        $(document).on('lead:date-range', () => window.appliedRanges.push([$('#leadDateFrom').val(), $('#leadDateTo').val()]));
    });
    await page.addScriptTag({path: 'public/admin/js/lead-calendar.js'});
}

test('quick ranges apply only on Apply, and Clear restores all dates', async ({page}) => {
    await setup(page);
    await page.locator('#leadCalendarToggle').click();
    await expect(page.locator('.lead-calendar-month')).toHaveCount(2);
    await page.getByRole('button', {name: 'Last 7 Days', exact: true}).click();
    expect(await page.evaluate(() => window.appliedRanges)).toEqual([]);
    await page.locator('#leadCalendarApply').click();
    await expect(page.locator('#leadCalendarLabel')).toHaveText('10-11-2026 – 16-11-2026');
    expect(await page.evaluate(() => window.appliedRanges)).toEqual([['2026-11-10', '2026-11-16']]);
    await page.locator('#leadCalendarToggle').click();
    await page.locator('#leadCalendarClear').click();
    await expect(page.locator('#leadCalendarLabel')).toHaveText('All dates');
    expect(await page.evaluate(() => window.appliedRanges.at(-1))).toEqual(['', '']);
});

test('custom range sorts reversed dates and Escape discards unapplied edits', async ({page}) => {
    await setup(page);
    await page.locator('#leadCalendarToggle').click();
    await page.locator('[data-calendar-date="2026-11-12"]').click();
    await expect(page.locator('#leadCalendarApply')).toBeDisabled();
    await page.locator('[data-calendar-date="2026-10-20"]').click();
    await page.locator('#leadCalendarApply').click();
    expect(await page.evaluate(() => window.appliedRanges.at(-1))).toEqual(['2026-10-20', '2026-11-12']);
    await page.locator('#leadCalendarToggle').click();
    await page.getByRole('button', {name: 'Today', exact: true}).click();
    await page.keyboard.press('Escape');
    await expect(page.locator('#leadCalendarPanel')).toBeHidden();
    await expect(page.locator('#leadCalendarLabel')).toHaveText('20-10-2026 – 12-11-2026');
    expect(await page.evaluate(() => window.appliedRanges.length)).toBe(1);
});

test('calendar fits mobile and month shortcuts handle month boundaries', async ({page}) => {
    await page.setViewportSize({width: 375, height: 760});
    await setup(page);
    await page.locator('#leadCalendarToggle').click();
    const box = await page.locator('#leadCalendarPanel').boundingBox();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(375);
    await page.getByRole('button', {name: 'Last Month', exact: true}).click();
    await page.locator('#leadCalendarApply').click();
    expect(await page.evaluate(() => window.appliedRanges.at(-1))).toEqual(['2026-10-01', '2026-10-31']);
});
