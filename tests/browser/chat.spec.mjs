import { test, expect } from "@playwright/test";
import { readFileSync, existsSync } from "node:fs";
import { resolve } from "node:path";

test.use({ channel: "chrome", viewport: { width: 1280, height: 900 } });

test("chat renders safely, sends messages, and starts groups using the actual UI", async ({
    page,
}) => {
    const errors = [];
    page.on("pageerror", (error) => errors.push(error.message));
    const messages = [
        {
            id: 1,
            conversation_id: 1,
            sender_id: 2,
            sender_name: "Sales User",
            client_id: "00000000-0000-4000-8000-000000000001",
            body: "<script>window.chatInjected=true</script>",
            created_at: "2026-10-31T10:00:00Z",
        },
    ];
    const members = [
        { id: 1, name: "Admin", last_read_message_id: 0 },
        { id: 2, name: "Sales User", last_read_message_id: 0 },
    ];
    const conversations = [
        {
            id: 1,
            name: "Sales User",
            type: "direct",
            members,
            unread_count: 1,
            latest_message: messages[0],
        },
    ];
    let created;
    const source = readFileSync(
        "resources/views/admin/chat/index.blade.php",
        "utf8"
    );
    let content = source
        .split("@section('content')")[1]
        .split("@endsection")[0];
    content = content
        .replace(/<link[^>]+>/g, "")
        .replace("{{ url('admin/chat') }}", "/admin/chat");
    const html = `<!doctype html><html><head><meta name="csrf-token" content="test-token">
        <link rel="stylesheet" href="/admin/libraries/css/bootstrap.min.css"><link rel="stylesheet" href="/admin/css/admin.css">
        <link rel="stylesheet" href="/admin/css/theme.css"><link rel="stylesheet" href="/admin/css/chat.css"></head><body class="chat-page">
        ${content}<script src="/admin/libraries/js/bootstrap.bundle.min.js"></script><script src="/admin/js/chat.js"></script></body></html>`;
    await page.route("http://chat.test/**", async (route) => {
        const url = new URL(route.request().url()),
            path = url.pathname;
        const json = (data) => route.fulfill({ json: data });
        if (path === "/")
            return route.fulfill({ contentType: "text/html", body: html });
        if (path === "/admin/chat/config")
            return json({
                user_id: 1,
                realtime_enabled: false,
                can_create_group: true,
            });
        if (path === "/admin/chat/users")
            return json({
                data: [{ id: 2, name: "Sales User" }],
                next_page_url: null,
            });
        if (path === "/admin/chat/conversations") {
            if (route.request().method() === "POST") {
                created = route.request().postDataJSON();
                const conversation = {
                    id: 2,
                    name: created.name,
                    type: created.type,
                    members,
                    unread_count: 0,
                    latest_message: null,
                };
                conversations.push(conversation);
                return json(conversation);
            }
            return json({ data: conversations, next_page_url: null });
        }
        if (path.endsWith("/messages")) {
            if (path.includes("/2/"))
                return json({ data: [], has_more: false });
            if (route.request().method() === "POST") {
                const input = route.request().postDataJSON();
                expect(input.client_id).toMatch(/^[\da-f-]{36}$/);
                const message = {
                    ...messages[0],
                    ...input,
                    id: 2,
                    sender_id: 1,
                    sender_name: "Admin",
                };
                messages.push(message);
                conversations[0].latest_message = message;
                return json(message);
            }
            return json({
                data: messages.filter(
                    (message) =>
                        message.id > Number(url.searchParams.get("after") || 0)
                ),
                has_more: false,
            });
        }
        if (path.endsWith("/read")) {
            members[0].last_read_message_id = route
                .request()
                .postDataJSON().message_id;
            conversations[0].unread_count = 0;
            return json({ status: true });
        }
        if (path.endsWith("/typing")) return json({ status: true });
        const file = resolve("public", "." + path);
        if (existsSync(file)) return route.fulfill({ path: file });
        return route.abort();
    });
    await page.goto("http://chat.test/");
    await page.getByRole("button", { name: /Sales User/ }).click();
    await expect(page.locator(".chat-message-body")).toHaveText(
        messages[0].body
    );
    expect(await page.evaluate(() => window.chatInjected)).toBeUndefined();
    await page
        .getByRole("textbox", { name: "Message", exact: true })
        .fill("Hello from the web CRM");
    await page.getByRole("button", { name: "Send", exact: true }).click();
    await expect(page.locator(".chat-message-body").last()).toHaveText(
        "Hello from the web CRM"
    );
    await expect(page.locator("#chatBody")).toHaveValue("");
    await page.getByRole("button", { name: "New chat" }).click();
    await page.locator("#chatKind").selectOption("group");
    await page.getByLabel("Group name").fill("Sales Team");
    await page.getByLabel("Sales User", { exact: true }).check();
    await page.getByRole("button", { name: "Start chat", exact: true }).click();
    await expect(page.locator("#chatTitle")).toHaveText("Sales Team");
    expect(created).toEqual({
        type: "group",
        name: "Sales Team",
        member_ids: [2],
    });
    expect(errors).toEqual([]);
});

test("bundled Echo exposes a working constructor for the Pusher client", async ({
    page,
}) => {
    await page.addScriptTag({ path: "public/admin/libraries/js/echo.iife.js" });
    expect(
        await page.evaluate(
            () =>
                typeof (typeof window.Echo === "function"
                    ? window.Echo
                    : window.Echo.default)
        )
    ).toBe("function");
});
