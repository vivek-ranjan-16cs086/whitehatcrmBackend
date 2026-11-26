import {test, expect} from '@playwright/test';
import {readFileSync} from 'node:fs';

test.use({channel: 'chrome'});

async function setup(page, editable = true) {
    const view = readFileSync('resources/views/admin/lead-management/index.blade.php', 'utf8');
    const choices = {status: ['active', 'deactive'], type: ['hot', 'cold'], inventory: ['2 BHK', '3 BHK'], visit_status: ['scheduled', 'visited'], deactivate_reason: ['Budget', 'Location']};
    const options = Object.fromEntries(Object.entries(choices).map(([field, values]) => [
        field, values.map(value => ({value, label: value})),
    ]));
    const script = view.slice(view.indexOf('const canEditLeadFields'), view.indexOf('let currentLeadView'))
        .replace(/@json\(auth\(\)->user\(\)->hasCrmPermission\('edit.lead'\)\)/, String(editable))
        .replace('@json($leadOptions)', JSON.stringify(options))
        .replace('@json($projects)', JSON.stringify([{id: 4, name: 'Project A'}, {id: 5, name: 'Project B'}]))
        .replace(/{{[\s\S]*?}}/g, '/update-field');
    await page.setContent('<meta name="csrf-token" content="test"><table id="leads-table"><tbody></tbody></table>');
    await page.addScriptTag({path: 'public/admin/libraries/js/jquery-3.7.1.min.js'});
    await page.evaluate(() => {
        window.requests = [];
        window.prompts = [];
        window.confirmChange = false;
        window.failSave = false;
        window.jQuery.fn.DataTable = () => ({ajax: {reload() {}}});
        window.Swal = {fire: async options => {
            window.prompts.push(options);
            return {isConfirmed: window.confirmChange, value: 'Budget'};
        }};
        window.jQuery.ajax = async options => {
            window.requests.push(options.data);
            if (window.failSave) throw {responseJSON: {errors: {value: ['Invalid choice']}}};
            return {success: true};
        };
    });
    await page.addScriptTag({content: script});
    await page.evaluate(() => {
        const row = {lead_id: 9, name: 'Buyer', edit_data: {status: 'active', type: 'cold', inventory: '2 BHK', project_id: 4, visit_status: 'scheduled', visit_date: '2026-11-16'}};
        document.querySelector('tbody').innerHTML = '<tr>' + Object.keys(row.edit_data).map(field => '<td>' + renderLeadField(field)(String(row.edit_data[field]), 'display', row) + '</td>').join('') + '</tr>';
    });
}

test('all selectable cells preserve current values and visit uses a date picker', async ({page}) => {
    await setup(page);
    await expect(page.locator('select.lead-field-select')).toHaveCount(5);
    await expect(page.locator('[data-field="project_id"]')).toHaveValue('4');
    await expect(page.locator('[data-field="type"]')).toHaveValue('cold');
    await expect(page.locator('input[type="date"]')).toHaveValue('2026-11-16');
    expect(await page.evaluate(() => renderLeadField('status')('Active', 'export', {}))).toBe('Active');
});

test('cancel and server failure restore the cell, confirmed changes submit only the selected field', async ({page}) => {
    await setup(page);
    const type = page.locator('[data-field="type"]');
    await type.selectOption('hot');
    await expect(type).toHaveValue('cold');
    expect(await page.evaluate(() => window.requests.length)).toBe(0);
    await page.evaluate(() => { window.confirmChange = true; });
    await type.selectOption('hot');
    await expect(type).toBeEnabled();
    expect(await page.evaluate(() => window.requests[0])).toEqual({id: '9', field: 'type', value: 'hot'});
    await page.evaluate(() => { window.failSave = true; });
    await type.selectOption('cold');
    await expect(type).toHaveValue('hot');
    expect(await page.evaluate(() => window.prompts.at(-1).text)).toBe('Invalid choice');
});

test('deactivation prompts for a reason and submits it with the status', async ({page}) => {
    await setup(page);
    await page.evaluate(() => { window.confirmChange = true; });
    await page.locator('[data-field="status"]').selectOption('deactive');
    await expect(page.locator('[data-field="status"]')).toBeEnabled();
    expect(await page.evaluate(() => window.prompts[0].input)).toBe('select');
    expect(await page.evaluate(() => window.requests[0])).toEqual({id: '9', field: 'status', value: 'deactive', deactivate_reason: 'Budget'});
});

test('read-only users see cell text instead of edit controls', async ({page}) => {
    await setup(page, false);
    await expect(page.locator('.lead-field-select')).toHaveCount(0);
});
