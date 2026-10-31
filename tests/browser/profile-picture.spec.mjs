import { test, expect } from "@playwright/test";
import { readFileSync, existsSync } from "node:fs";
import { resolve } from "node:path";

test.use({ channel: "chrome" });

test("profile picture opens and saves on the current page with circular avatars", async ({
    page,
}) => {
    const layout = readFileSync(
        "resources/views/layouts/admin.blade.php",
        "utf8"
    );
    const button = layout.match(
        /<button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#adminProfilePictureModal">[\s\S]*?<\/button>/
    )[0];
    const modal = readFileSync(
        "resources/views/admin/partials/profile-picture-modal.blade.php",
        "utf8"
    )
        .replace(/@if\(auth\(\)->user\(\)->profile_pic\)[\s\S]*?@endif/g, "U")
        .replace(
            "{{ url('admin/chat/profile-picture') }}",
            "/admin/chat/profile-picture"
        );
    await page.route("http://profile.test/**", (route) => {
        const path = new URL(route.request().url()).pathname;
        if (path === "/dashboard")
            return route.fulfill({
                contentType: "text/html",
                body: `<!doctype html><html><head><meta name="csrf-token" content="test"><link rel="stylesheet" href="/admin/libraries/css/bootstrap.min.css"><link rel="stylesheet" href="/admin/css/theme.css"></head><body>
            ${button}<div class="user-dropdown-avatar">U</div><button class="chat-profile-picture" id="chatProfileButton">U</button>
            <aside class="sidebar"><ul class="sidebar-nav"><li class="nav-item"><a href="#">Dashboard</a></li></ul></aside>
            ${modal}<script src="/admin/libraries/js/bootstrap.bundle.min.js"></script><script src="/admin/js/profile-picture.js"></script></body></html>`,
            });
        if (path === "/admin/chat/profile-picture")
            return route.fulfill({
                json: { profile_picture_url: "/avatar.png" },
            });
        if (path === "/avatar.png")
            return route.fulfill({
                contentType: "image/png",
                body: Buffer.from(
                    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=",
                    "base64"
                ),
            });
        const file = resolve("public", "." + path);
        return existsSync(file) ? route.fulfill({ path: file }) : route.abort();
    });
    await page.goto("http://profile.test/dashboard");
    await page
        .getByRole("button", { name: "Profile picture", exact: true })
        .click();
    await expect(page.getByRole("dialog")).toBeVisible();
    await expect(page).toHaveURL("http://profile.test/dashboard");
    await page
        .getByLabel("Choose a picture")
        .setInputFiles({
            name: "avatar.png",
            mimeType: "image/png",
            buffer: Buffer.from("image-test"),
        });
    await page.getByRole("button", { name: "Save picture" }).click();
    await expect(page.getByRole("dialog")).toBeHidden();
    await expect(page.locator(".user-dropdown-avatar img")).toHaveAttribute(
        "src",
        "/avatar.png"
    );
    await expect(page.locator("#chatProfileButton")).toHaveCSS(
        "border-radius",
        "50%"
    );
    await expect(page.locator(".sidebar .nav-item a")).toHaveCSS(
        "font-weight",
        "700"
    );
    await expect(page).toHaveURL("http://profile.test/dashboard");
});
