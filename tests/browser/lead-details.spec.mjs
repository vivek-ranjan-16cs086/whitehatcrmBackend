import {test, expect} from '@playwright/test';
test.use({channel: 'chrome'});

test('eye button loads full lead details and paginates history safely inside modal', async ({page}) => {
    await page.setContent(`<button class="viewBtn" data-id="12">View</button><div id="viewLeadModal" data-details-url="/lead-managment">
        <h5 id="viewLeadModalLabel"></h5><div id="leadViewStatus"></div><div id="leadViewContent" hidden>
        <button data-bs-target="#leadDetailsPane">Details</button><p id="leadViewSummary"></p><dl id="leadViewDetails"></dl>
        ${['timeline','responses','ownership'].map(s=>`<span data-count="${s}"></span><div data-history="${s}"></div><button data-load-history="${s}" hidden>Load more</button>`).join('')}</div></div>`);
    await page.addScriptTag({path:'public/admin/libraries/js/jquery-3.7.1.min.js'});
    await page.evaluate(() => {
        window.bootstrap = {Tab: {getOrCreateInstance:()=>({show(){}})}, Modal: {getOrCreateInstance:()=>({show(){}})}};
        window.jQuery.getJSON = (url, params) => {
            const deferred = window.jQuery.Deferred();
            const history = {items:[{id:1,type:'COMMENT',user_name:'Owner',message:'<img src=x onerror=alert(1)>',created_at:'31 Oct 2026 IST'}],total:26,page:1,has_more:true};
            setTimeout(()=>deferred.resolve(params.section ? {...history,items:[{...history.items[0],message:'Older event'}],page:2,has_more:false} : {
                id:12,name:'Full lead',current_owner:'Owner',activity_scope:'Complete recorded history',
                fields:[{label:'Message',value:'Full untruncated message'}], sections:{timeline:history,responses:{items:[],total:0,page:1,has_more:false},ownership:{items:[],total:0,page:1,has_more:false}}
            }), 1);
            const promise=deferred.promise(); promise.abort=()=>{}; return promise;
        };
    });
    await page.addScriptTag({path:'public/admin/js/lead-details.js'});
    await page.getByRole('button',{name:'View',exact:true}).click();
    await expect(page.locator('#leadViewContent')).toBeVisible();
    await expect(page.locator('#leadViewDetails')).toContainText('Full untruncated message');
    await expect(page.locator('[data-history="timeline"]')).toContainText('<img src=x onerror=alert(1)>');
    await expect(page.locator('[data-history="timeline"] img')).toHaveCount(0);
    await page.locator('[data-load-history="timeline"]').click();
    await expect(page.locator('[data-history="timeline"] article')).toHaveCount(2);
    await expect(page.locator('[data-load-history="timeline"]')).toBeHidden();
});
