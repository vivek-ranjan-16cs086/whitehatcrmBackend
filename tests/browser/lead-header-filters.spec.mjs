import {test, expect} from '@playwright/test';
import {readFileSync} from 'node:fs';

test.use({channel: 'chrome'});

test('header filters combine, synchronize scrolling copies, clear individually, and replace a status tab', async ({page}) => {
    const view = readFileSync('resources/views/admin/lead-management/index.blade.php', 'utf8');
    const header = view.match(/<thead>[\s\S]*?<\/thead>/)[0].replace(/@foreach[\s\S]*?@endforeach/g, '<option value="choice">Database choice</option>');
    const reset = view.match(/\$\(document\)\.on\('click', '#resetLeadFilters',[\s\S]*?\n\}\);/)[0];
    const script = view.slice(view.indexOf('const leadHeaderFilters ='), view.indexOf('let currentLeadView'));
    await page.route('https://app.test/**', route => route.fulfill({contentType: 'text/html', body: '<body></body>'}));
    await page.goto('https://app.test/');
    await page.setContent('<div class="dataTables_scrollHead"><table>' + header + '</table></div><table id="leads-table">' + header + '</table><button id="resetLeadFilters">Reset</button>');
    await page.addScriptTag({path: 'public/admin/libraries/js/jquery-3.7.1.min.js'});
    await page.evaluate(() => {
        window.currentLeadView = 'active';
        window.pendingAttention = null;
        window.requests = [];
        window.updateLeadFilterButtons = () => {};
        window.clearLeadDateRange = reload => { window.dateRangeCleared = reload === false; };
        window.pendingLeadId = 12;
        window.jQuery.fn.DataTable = () => ({
            ajax: {reload() { window.requests.push({...leadHeaderFilters}); }},
            search(value) { window.searchValue = value; }, columns: () => ({search(value) { window.columnSearchValue = value; }}),
        });
        window.setLeadView = view => { window.currentLeadView = view; $('#leads-table').DataTable().ajax.reload(); };
    });
    await page.addScriptTag({content: script + '\n' + reset});
    const source = page.locator('.dataTables_scrollHead [data-lead-column-filter="source_website"]');
    const owner = page.locator('.dataTables_scrollHead [data-lead-column-filter="assigned_to"]');
    await source.selectOption('choice');
    await owner.selectOption('choice');
    expect(await page.evaluate(() => window.requests.at(-1))).toEqual({source_website: 'choice', assigned_to: 'choice'});
    await expect(page.locator('#leads-table [data-lead-column-filter="source_website"]')).toHaveValue('choice');
    await source.selectOption('');
    expect(await page.evaluate(() => window.requests.at(-1))).toEqual({assigned_to: 'choice'});
    await page.locator('.dataTables_scrollHead [data-lead-column-filter="status"]').selectOption('choice');
    expect(await page.evaluate(() => window.currentLeadView)).toBe('total');
    await owner.selectOption('');
    await page.locator('.dataTables_scrollHead [data-lead-column-filter="status"]').selectOption('');
    expect(await page.evaluate(() => window.requests.at(-1))).toEqual({});
    await expect(owner).toHaveValue('');
    await source.selectOption('choice');
    await owner.selectOption('choice');
    await page.locator('#resetLeadFilters').click();
    expect(await page.evaluate(() => window.requests.at(-1))).toEqual({});
    expect(await page.evaluate(() => window.dateRangeCleared)).toBe(true);
    expect(await page.evaluate(() => window.searchValue)).toBe('');
    expect(await page.evaluate(() => window.columnSearchValue)).toBe('');
    expect(await page.evaluate(() => window.pendingLeadId)).toBe(null);
    await expect(source).toHaveValue('');
    await expect(owner).toHaveValue('');
});
