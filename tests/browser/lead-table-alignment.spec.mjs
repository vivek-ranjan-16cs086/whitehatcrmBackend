import {test, expect} from '@playwright/test';
import {readFileSync} from 'node:fs';

test.use({channel: 'chrome'});

test('scrolling table header aligns with body cells and uses matching selection checkboxes', async ({page}) => {
    const view = readFileSync('resources/views/admin/lead-management/index.blade.php', 'utf8');
    const header = view.match(/<thead>[\s\S]*?<\/thead>/)[0].replace(/@foreach[\s\S]*?@endforeach/g, '<option value="choice">Filter choice</option>');
    const selection = view.slice(view.indexOf('const selectedLeadIds'), view.indexOf('let currentLeadView'));
    await page.setViewportSize({width: 1200, height: 800});
    await page.setContent('<div style="width:1100px"><table id="leads-table" class="table leads-table">' + header + '<tbody></tbody></table></div><span id="leadSelectionCount"></span>');
    for (const path of ['public/admin/libraries/css/bootstrap.min.css', 'public/admin/libraries/css/dataTables.bootstrap5.min.css', 'public/admin/css/table.css']) {
        await page.addStyleTag({path});
    }
    for (const path of ['public/admin/libraries/js/jquery-3.7.1.min.js', 'public/admin/libraries/js/jquery.dataTables.min.js']) {
        await page.addScriptTag({path});
    }
    await page.addScriptTag({content: selection});
    await page.evaluate(() => {
        $('#leads-table').DataTable({
            scrollX: true, autoWidth: true, order: [], paging: false, searching: false,
            data: [{lead_id: 1, name: 'Sample lead'}],
            columns: [
                {data: null, width: '36px', className: 'lead-selection-cell', orderable: false, render: renderLeadSelection},
                {data: 'name'},
                ...Array.from({length: 13}, (_, i) => ({data: null, render: () => i === 5 ? '<select style="width:240px"><option>Business Executive</option></select>' : 'Example value'})),
            ],
            drawCallback: syncLeadSelection,
        });
    });
    const layout = await page.evaluate(() => {
        const header = [...document.querySelectorAll('.dataTables_scrollHead th')];
        const cells = [...document.querySelectorAll('#leads-table tbody tr:first-child td')];
        return header.map((th, i) => ({
            headerLeft: th.getBoundingClientRect().left,
            cellLeft: cells[i].getBoundingClientRect().left,
            headerWidth: th.getBoundingClientRect().width,
            cellWidth: cells[i].getBoundingClientRect().width,
            headerAlign: getComputedStyle(th).textAlign,
            cellAlign: getComputedStyle(cells[i]).textAlign,
        }));
    });
    expect(layout).toHaveLength(15);
    for (const column of layout) {
        expect(Math.abs(column.headerLeft - column.cellLeft)).toBeLessThan(2);
        expect(Math.abs(column.headerWidth - column.cellWidth)).toBeLessThan(2);
        expect(column.headerAlign).toBe(column.cellAlign);
    }
    const checkboxes = await page.locator('.dataTables_scrollHead .lead-select-checkbox, #leads-table tbody .lead-select-checkbox').evaluateAll(inputs => inputs.map(input => ({
        width: input.getBoundingClientRect().width,
        center: input.getBoundingClientRect().left + input.getBoundingClientRect().width / 2,
    })));
    expect(checkboxes).toHaveLength(2);
    expect(checkboxes[0].width).toBe(18);
    expect(checkboxes[1].width).toBe(18);
    expect(Math.abs(checkboxes[0].center - checkboxes[1].center)).toBeLessThan(2);
    await page.locator('.dataTables_scrollHead input').check();
    await expect(page.locator('#leads-table tbody input')).toBeChecked();
});
