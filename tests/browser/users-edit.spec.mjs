import {test, expect} from '@playwright/test';
import {readFileSync} from 'node:fs';

test.use({channel:'chrome'});

test('user edit populates stored usernames and suggests one for older blank accounts', async ({page}) => {
    const view=readFileSync('resources/views/admin/users/index.blade.php','utf8');
    const scripts=view.split("@push('scripts')")[1];
    const inline=scripts.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace(/{{[\s\S]*?}}/g,'/stub').replace(/loadAjaxList\(\);/g,'');
    await page.setContent(`<meta name="csrf-token" content="test"><button class="js-edit-user">Edit</button>
        <div id="userModal"><form id="userForm"><input id="name"><input id="username"><input id="email">
        <input id="password"><input id="password_confirmation"><select id="role"><option value="sales">Sales</option></select>
        <button id="submitBtn" type="submit"><span id="submitBtnText"></span></button></form>
        <span id="usernameStatus"></span><span id="usernameMessage"></span><div id="passwordFields"></div>
        <span id="modalTitle"></span><span id="passwordLabel"></span><span id="confirmPasswordLabel"></span><span id="passwordHelp"></span></div>`);
    await page.addScriptTag({path:'public/admin/libraries/js/jquery-3.7.1.min.js'});
    await page.evaluate(()=>{
        window.ajaxChecks=0;
        window.jQuery.fn.modal=function(){return this;};
        window.jQuery.ajax=options=>{window.ajaxChecks++;setTimeout(()=>options.success({available:true}),5);};
    });
    await page.addScriptTag({content:inline});
    await page.evaluate(()=>document.querySelector('.js-edit-user').dataset.user=JSON.stringify({id:12,name:'Existing User',username:'existing.user',email:'existing@example.com',role:'sales'}));
    await page.getByRole('button',{name:'Edit',exact:true}).click();
    await expect(page.locator('#username')).toHaveValue('existing.user');
    await expect(page.locator('#username')).toHaveClass(/is-valid/);
    expect(await page.evaluate(()=>window.ajaxChecks)).toBe(0);

    await page.evaluate(()=>document.querySelector('.js-edit-user').dataset.user=JSON.stringify({id:13,name:'Legacy User',username:null,email:'legacy.user@example.com',role:'sales'}));
    await page.getByRole('button',{name:'Edit',exact:true}).click();
    await expect(page.locator('#username')).toHaveValue('legacy.user');
    await expect(page.locator('#username')).toHaveClass(/is-valid/);
    expect(await page.evaluate(()=>window.ajaxChecks)).toBe(1);
});
