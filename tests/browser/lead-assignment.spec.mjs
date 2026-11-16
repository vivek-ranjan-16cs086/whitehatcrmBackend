import {test, expect} from '@playwright/test';
import {readFileSync} from 'node:fs';

test.use({channel: 'chrome'});

async function setup(page) {
    const view = readFileSync('resources/views/admin/lead-management/index.blade.php', 'utf8');
    const assignment = view.slice(view.indexOf('const canAssignLeadsInline'), view.indexOf('const leadViewTitles'))
        .replace(/@json\(auth\(\)->user\(\)->hasRole\('super-admin', 'web'\)\)/, 'true')
        .replace('@json($assignmentUsers)', JSON.stringify([{id: 7, text: 'Alice — Sales'}, {id: 8, text: 'Bob — Manager'}]))
        .replace(/{{[\s\S]*?}}/g, '/assign');
    const editSelect = view.slice(view.indexOf('function initSalesPersonSelect('), view.indexOf('// Initialize Select2 for Add Lead Modal'))
        .replace(/{{[\s\S]*?}}/g, '/assignees');
    await page.setContent('<meta name="csrf-token" content="test"><table id="leads-table"><tbody></tbody></table><div id="editLeadModal"><select id="edit_assigned"></select></div>');
    await page.addScriptTag({path: 'public/admin/libraries/js/jquery-3.7.1.min.js'});
    await page.addScriptTag({path: 'public/admin/libraries/js/select2.min.js'});
    await page.evaluate(() => {
        window.confirmAssignment = false;
        window.failAssignment = false;
        window.requests = [];
        window.warnings = [];
        window.reloads = 0;
        window.Swal = {fire: async options => {
            window.warnings.push(options);
            return {isConfirmed: window.confirmAssignment};
        }};
        window.jQuery.fn.DataTable = () => ({ajax: {reload: () => window.reloads++}});
        window.jQuery.ajax = options => {
            window.requests.push(options.data);
            return window.failAssignment ? Promise.reject({responseJSON: {message: 'Save failed'}}) : Promise.resolve({success: true});
        };
    });
    await page.addScriptTag({content: assignment + '\n' + editSelect});
}

test('inline dropdown preserves current owner, includes roles and keeps export text', async ({page}) => {
    await setup(page);
    await page.evaluate(() => {
        const row = {lead_id: 12, name: 'Buyer', edit_data: {assigned_to: 8}};
        document.querySelector('tbody').innerHTML = '<tr><td>' + renderLeadAssignee('Bob', 'display', row) + '</td></tr>';
        window.exportOwner = renderLeadAssignee('Bob', 'export', row);
    });
    await expect(page.locator('.lead-assignee-select')).toHaveValue('8');
    await expect(page.locator('.lead-assignee-select')).toContainText('Alice — Sales');
    expect(await page.evaluate(() => window.exportOwner)).toBe('Bob');
});

test('assignment warns, cancellation restores owner, confirmation saves and failure restores owner', async ({page}) => {
    await setup(page);
    await page.evaluate(() => {
        document.querySelector('tbody').innerHTML = '<tr><td>' + renderLeadAssignee('Unassigned', 'display', {lead_id: 12, name: 'Buyer', edit_data: {assigned_to: null}}) + '</td></tr>';
    });
    const select = page.locator('.lead-assignee-select');
    await select.selectOption('7');
    await expect(select).toHaveValue('');
    expect(await page.evaluate(() => window.requests.length)).toBe(0);
    expect(await page.evaluate(() => window.warnings[0].icon)).toBe('warning');
    await page.evaluate(() => { window.confirmAssignment = true; });
    await select.selectOption('7');
    await expect(select).toHaveValue('7');
    expect(await page.evaluate(() => window.requests)).toEqual([{id: '12', assigned_to: '7'}]);
    expect(await page.evaluate(() => window.reloads)).toBe(1);
    await page.evaluate(() => { window.failAssignment = true; });
    await select.selectOption('8');
    await expect(select).toHaveValue('7');
    await expect(select).toBeEnabled();
});

test('editing an unassigned lead after assigned leads clears previous Select2 options', async ({page}) => {
    await setup(page);
    await page.evaluate(() => initSalesPersonSelect(7, 'Alice — Sales'));
    await expect(page.locator('#edit_assigned')).toHaveValue('7');
    await page.evaluate(() => {
        $('#edit_assigned').select2('destroy');
        initSalesPersonSelect(null, null);
    });
    await expect(page.locator('#edit_assigned')).toHaveValue('');
    await expect(page.locator('#edit_assigned option')).toHaveCount(1);
    await expect(page.locator('#select2-edit_assigned-container')).not.toContainText('Alice');
    await page.evaluate(() => {
        $('#edit_assigned').select2('destroy');
        initSalesPersonSelect(8, 'Bob — Manager');
    });
    await expect(page.locator('#edit_assigned')).toHaveValue('8');
    await expect(page.locator('#edit_assigned option')).toHaveCount(2);
});
