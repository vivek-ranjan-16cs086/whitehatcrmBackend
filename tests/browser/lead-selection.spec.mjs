import {test, expect} from '@playwright/test';
import {readFileSync} from 'node:fs';

test.use({channel: 'chrome'});

test('row selection, page selection, pagination and clear keep the header and count in sync', async ({page}) => {
    const view = readFileSync('resources/views/admin/lead-management/index.blade.php', 'utf8');
    const script = view.slice(view.indexOf('const selectedLeadIds'), view.indexOf('let currentLeadView'));
    await page.setContent('<table id="leads-table"><thead><tr><th><input id="selectAllLeads" type="checkbox"></th></tr></thead><tbody></tbody></table><span id="leadSelectionCount"></span><button id="clearLeadSelection">Clear selection</button>');
    await page.addScriptTag({path: 'public/admin/libraries/js/jquery-3.7.1.min.js'});
    await page.addScriptTag({content: script});
    const showPage = async ids => page.evaluate(ids => {
        document.querySelector('tbody').innerHTML = ids.map(id => '<tr><td>' + renderLeadSelection(null, 'display', {lead_id: id, name: 'Buyer ' + id}) + '</td></tr>').join('');
        syncLeadSelection();
    }, ids);

    await showPage([1, 2]);
    await page.locator('.lead-row-select[value="1"]').check();
    await expect(page.locator('#leadSelectionCount')).toHaveText('1 selected');
    expect(await page.locator('#selectAllLeads').evaluate(input => input.indeterminate)).toBe(true);
    await page.locator('#selectAllLeads').check();
    await expect(page.locator('.lead-row-select:checked')).toHaveCount(2);
    await expect(page.locator('#leadSelectionCount')).toHaveText('2 selected');
    await showPage([3]);
    await expect(page.locator('#selectAllLeads')).not.toBeChecked();
    await expect(page.locator('#leadSelectionCount')).toHaveText('2 selected');
    await page.locator('#selectAllLeads').check();
    await expect(page.locator('#leadSelectionCount')).toHaveText('3 selected');
    await showPage([1, 2]);
    await expect(page.locator('.lead-row-select:checked')).toHaveCount(2);
    await page.locator('#selectAllLeads').uncheck();
    await expect(page.locator('#leadSelectionCount')).toHaveText('1 selected');
    await page.locator('#clearLeadSelection').click();
    await expect(page.locator('#leadSelectionCount')).toHaveText('0 selected');
    await expect(page.locator('#clearLeadSelection')).toBeHidden();
    await showPage([]);
    await expect(page.locator('#selectAllLeads')).toBeDisabled();
});
